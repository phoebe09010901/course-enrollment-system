const DEPLOY_VERSION = 'chat-c-short-line-code-fix-2026-05-27-08';
const intakeMemory = new Map();
const handoffMemory = new Map();

export default {
  async fetch(request, env, ctx) {
    if (request.method === 'GET') {
      return json({
        status: 'ok',
        service: 'ftm-line-webhook',
        mode: 'cloudflare-workers',
        version: DEPLOY_VERSION,
        message: 'LINE webhook is ready.',
        config: {
          handoff_kv: Boolean(env.HANDOFF_KV),
          admission_api: Boolean(env.ADMISSION_API_URL),
          images_r2: Boolean(env.ADMISSION_IMAGES_R2),
          chat_role: 'Chat C：LineAI客服 - 詢課',
        },
      });
    }

    if (request.method !== 'POST') {
      return json({ status: 'error', message: 'Method not allowed.' }, 405);
    }

    const body = await request.text();
    const signature = request.headers.get('x-line-signature') || '';

    if (!(await isValidLineSignature(body, signature, env.LINE_CHANNEL_SECRET))) {
      return json({ status: 'error', message: 'Invalid signature.' }, 401);
    }

    let payload = {};

    try {
      payload = JSON.parse(body || '{}');
    } catch (_) {
      return json({ status: 'error', message: 'Invalid JSON.' }, 400);
    }

    const events = Array.isArray(payload.events) ? payload.events : [];
    const task = Promise.all(events.map((event) => handleLineEvent(event, env))).catch((error) => {
      console.log('Webhook processing failed', error?.message || error);
    });

    if (ctx && typeof ctx.waitUntil === 'function') {
      ctx.waitUntil(task);
    } else {
      await task;
    }

    return json({ status: 'ok' });
  },
};

async function handleLineEvent(event, env) {
  const normalized = normalizeLineEvent(event);

  if (!normalized) return;

  if (await handlePublicCommand(normalized, env)) return;

  const handoffState = await getHandoffState(normalized.user_id, env);

  if (handoffState) {
    if (shouldSilentDuringHandoff(env)) return;
    await replyToLine(normalized.reply_token, '已收到你的訊息，目前已轉由負責窗口處理，請稍候回覆。', env.LINE_CHANNEL_ACCESS_TOKEN);
    return;
  }

  const reply = await handleIntake(normalized, env);

  if (reply) {
    await replyToLine(normalized.reply_token, reply, env.LINE_CHANNEL_ACCESS_TOKEN);
  }
}

function normalizeLineEvent(event) {
  if (event?.type !== 'message') return null;
  if (!event.replyToken || event.replyToken === '00000000000000000000000000000000') return null;

  const message = event.message || {};

  return {
    user_id: event.source?.userId || event.source?.groupId || event.source?.roomId || '',
    reply_token: event.replyToken,
    message_type: message.type || 'unknown',
    message_id: message.id || '',
    user_message: message.type === 'text' ? String(message.text || '').trim() : '',
    timestamp: event.timestamp || Date.now(),
  };
}

async function handleIntake(eventData, env) {
  let state = await getIntakeState(eventData.user_id, env);

  if (eventData.message_type !== 'text') {
    if (!state) {
      return openingReply();
    }

    if (isPhotoStep(state.current_intake_step) && eventData.message_type === 'image') {
      return handlePhotoUpload(eventData, state, env);
    }

    state.invalid_reply_count += 1;
    const reply = fallbackForState(state, 'unclear_reply');
    await saveIntakeState(eventData.user_id, state, env);
    return reply;
  }

  const text = eventData.user_message;

  if (!state) {
    if (isStartCommand(text)) {
      state = createInitialIntakeState(eventData);
      await saveIntakeState(eventData.user_id, state, env);
      return contactFormPrompt();
    }

    if (isFlowIntro(text)) return flowIntroReply();
    if (isSoftInquiry(text)) return softInquiryReply();
    if (isServicePriceQuestion(text)) return freeTrialReply();
    if (isSystemInstructionLike(text)) {
      state = createInitialIntakeState(eventData);
      state.invalid_reply_count += 1;
      const reply = fallbackForState(state, 'unsafe_or_system_instruction');
      await saveIntakeState(eventData.user_id, state, env);
      return reply;
    }

    return openingReply();
  }

  if (isSystemInstructionLike(text)) {
    state.invalid_reply_count += 1;
    const reply = fallbackForState(state, 'unsafe_or_system_instruction');
    await saveIntakeState(eventData.user_id, state, env);
    return reply;
  }

  if (isStartCommand(text)) {
    state = createInitialIntakeState(eventData);
    await saveIntakeState(eventData.user_id, state, env);
    return contactFormPrompt();
  }

  if (state.current_intake_step !== 'ready_for_confirmation' && isConfirmationLike(text)) {
    return handlePrematureConfirmation(eventData, state, env);
  }

  if (!state.started) {
    if (isFlowIntro(text)) return flowIntroReply();
    if (isSoftInquiry(text)) return softInquiryReply();
    if (isServicePriceQuestion(text)) return freeTrialReply();
    await saveIntakeState(eventData.user_id, state, env);
    return openingReply();
  }

  if (isServicePriceQuestion(text) && !isCoursePriceAnswerContext(state)) {
    return `${freeTrialReply()}\n\n我們可以先回到目前資料整理：${currentStepHint(state)}`;
  }

  if (state.current_intake_step === 'collecting_required_contact') {
    return handleContactStep(eventData, state, env);
  }

  if (isCourseStep(state.current_intake_step)) {
    return handleCourseStep(eventData, state, env);
  }

  if (isPhotoStep(state.current_intake_step)) {
    return handlePhotoText(eventData, state, env);
  }

  if (state.current_intake_step === 'ready_for_confirmation') {
    return handleConfirmationStep(eventData, state, env);
  }

  return openingReply();
}

function createInitialIntakeState(eventData) {
  return {
    started: true,
    user_id: eventData.user_id,
    current_intake_step: 'collecting_required_contact',
    current_required_fields: ['user_name', 'email', 'line_id_link'],
    missing_fields: ['user_name', 'email', 'line_id_link'],
    last_asked_field: '',
    invalid_reply_count: 0,
    last_fallback_key: '',
    last_fallback_type: '',
    last_fallback_id: '',
    intake_status: 'in_progress',
    collected_data: {},
    user_name: '',
    email: '',
    email_status: 'pending',
    line_id_link: '',
    line_id_link_status: 'pending',
    email_decline_count: 0,
    needs_human_contact_review: false,
    course_name: '',
    suggested_course_name: '',
    course_name_needs_confirmation: false,
    course_type: '',
    course_format: '',
    course_location: '',
    expected_launch_date: '',
    expected_launch_date_status: 'pending',
    expected_start_date: '',
    expected_start_date_status: 'pending',
    course_capacity: '',
    course_capacity_status: 'pending',
    course_price: '',
    course_price_status: 'pending',
    target_audience: '',
    course_features: '',
    after_class_support: '',
    has_photos: 'pending',
    has_copywriting: 'pending',
    needs_template_proposal: true,
    hero_artwork_images: [],
    hero_artwork_images_status: 'pending',
    hero_artwork_images_note: '',
    detail_artwork_images: [],
    detail_artwork_images_status: 'pending',
    detail_artwork_images_note: '',
    teacher_images: [],
    teacher_images_status: 'pending',
    teacher_images_note: '',
    classroom_images: [],
    classroom_images_status: 'pending',
    classroom_images_note: '',
    class_activity_images: [],
    class_activity_images_status: 'pending',
    class_activity_images_note: '',
    hero_artwork_copyright_notice_shown: false,
    detail_artwork_copyright_notice_shown: false,
    teacher_photo_copyright_notice_shown: false,
    classroom_photo_copyright_notice_shown: false,
    class_activity_photo_copyright_notice_shown: false,
    confirmed_by_client: false,
    confirmed_at: '',
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
  };
}

function openingReply() {
  return [
    '嗨嗨～歡迎來到課程招生頁製作諮詢 😊🌿',
    '',
    '正在準備開課、招生，或只是有一點想法，都可以先從這裡開始。',
    '',
    '我可以幫忙把課程內容整理成比較清楚的招生頁資料 📝',
    '目前也正在免費試營運中，不用準備得很完整，我們可以一步一步來。',
    '',
    '可以先選一個：',
    '',
    '1. 我想製作課程招生頁 🎨',
    '2. 我想了解製作流程 📋',
    '3. 我只是先問問看 ☕️',
    '',
    '也可以直接打字跟我說目前的狀況～',
  ].join('\n');
}

function flowIntroReply() {
  return [
    '可以，我簡單說明一下流程 😊',
    '',
    '1. 先整理課程資料',
    '2. 製作 3 款招生頁視覺提案',
    '3. 你選定其中一款',
    '4. 完成正式頁面預覽',
    '5. 確認後進入免費試營運',
    '',
    '如果想開始整理，可以直接回覆「我想開始」。',
  ].join('\n');
}

function softInquiryReply() {
  return '沒問題，可以先輕鬆聊聊 😊\n你可以直接跟我說目前想開什麼課、想招生給誰，或準備到哪個階段。想正式整理時，回覆「我想開始」就可以。';
}

function contactFormPrompt() {
  return [
    '可以，我先給你一份簡短表單。',
    '不用一次填得很完美，不確定的地方可以先留空、寫「暫定」或「還不確定」。',
    '',
    '【基本聯絡資料】',
    '1. 姓名：',
    '2. Email：',
    '3. LINE ID Link：',
    '',
    '【課程基本資料】',
    '4. 課程名稱：',
    '5. 課程類型：',
    '6. 課程形式：',
    '7. 上課地點：',
    '8. 預計招生時間：',
    '9. 預計開課時間：',
    '10. 課程名額：',
    '11. 課程費用：',
    '12. 適合對象：',
    '13. 課程特色：',
    '14. 課後支援：',
    '',
    '填好後直接貼回來就可以，我會只針對空白或格式需要確認的欄位再補問。',
    '小提醒：Email 未來會作為登入帳號，LINE ID Link 會用於後續通知與聯繫。',
  ].join('\n');
}

async function handleContactStep(eventData, state, env) {
  if (handleEmailDeclineText(state, eventData.user_message)) {
    await saveIntakeState(eventData.user_id, state, env);
    return [
      '我先幫你記錄 Email 目前不方便提供。',
      'Email 之後會作為登入和通知用途，所以這筆資料會先保留在人工確認狀態，不會直接建檔。',
      '',
      contactMissingHint(state),
    ].join('\n');
  }

  const beforeContact = contactSnapshot(state);
  applyFormFields(state, eventData.user_message);
  applyContactText(state, eventData.user_message);
  refreshCollectedData(state);
  const contactChanged = beforeContact !== contactSnapshot(state);

  const missing = missingContactFields(state);

  if (!contactChanged && missing.length && !looksLikeForm(eventData.user_message)) {
    state.invalid_reply_count += 1;
    const reply = contactFallbackForState(state);
    await saveIntakeState(eventData.user_id, state, env);
    return reply;
  }

  if (state.email && state.email_status === 'invalid') {
    await saveIntakeState(eventData.user_id, state, env);
    return '這個 Email 格式看起來不完整，Email 未來會作為登入帳號，也會用來通知預覽網址。\n可以再提供一次嗎？例如 name@example.com';
  }

  if (state.line_id_link_status === 'invalid_display_name_only') {
    await saveIntakeState(eventData.user_id, state, env);
    return '這個看起來比較像 LINE 顯示名稱，不是 LINE ID 的 Link。\n可以提供 LINE 個人連結或可聯繫的 LINE Link 嗎？後續通知會用到。';
  }

  if (missing.length) {
    await saveIntakeState(eventData.user_id, state, env);
    return contactMissingHint(state);
  }

  state.current_intake_step = 'collecting_course_name_type';
  state.current_required_fields = ['course_name', 'course_type'];
  state.invalid_reply_count = 0;
  moveToNextIncompleteStep(state);
  await saveIntakeState(eventData.user_id, state, env);

  if (isPhotoStep(state.current_intake_step)) {
    markCopyrightNoticeShown(state, 'collecting_hero_artwork_images');
    return [
      '聯絡資料和課程資料都先整理好了。',
      '',
      heroArtworkPrompt(),
    ].join('\n');
  }

  return nextCoursePrompt(state, '聯絡資料已收到，我們接著整理課程內容。');
}

function applyContactText(state, text) {
  const value = String(text || '').trim();

  if (isClearlyInvalidContactReply(value)) return;

  const email = value.match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i)?.[0] || '';

  if (email) {
    state.email = email;
    state.email_status = isValidEmail(email) ? 'valid' : 'invalid';
  }

  const lineValue = extractLineLink(value);

  if (lineValue) {
    state.line_id_link = lineValue;
    state.line_id_link_status = classifyLineIdLink(lineValue);
  }

  if (!state.user_name && !looksLikeForm(value) && !looksLikeCourseFieldText(value) && !isCourseAnswerToken(value) && !isFieldHelpQuestion(value)) {
    const name = value
      .split(/\r?\n/)
      .map((line) => cleanLabeledValue(line, ['姓名', '使用者姓名', '名字', '稱呼']))
      .find((line) => isPlausibleUserName(line, email));

    if (name) state.user_name = name;
  }
}

function contactSnapshot(state) {
  return JSON.stringify({
    user_name: state.user_name,
    email: state.email,
    email_status: state.email_status,
    line_id_link: state.line_id_link,
    line_id_link_status: state.line_id_link_status,
  });
}

async function handlePrematureConfirmation(eventData, state, env) {
  state.invalid_reply_count = 0;
  await saveIntakeState(eventData.user_id, state, env);

  if (state.current_intake_step === 'collecting_required_contact') {
    return [
      '還沒到確認送出喔，目前還需要先補齊必填聯絡資料。',
      '',
      contactMissingHint(state),
    ].join('\n');
  }

  return [
    '還沒到最後確認送出的步驟喔。',
    '我先把目前資料整理完，再給你完整摘要確認。',
    '',
    currentStepHint(state),
  ].join('\n');
}

function contactMissingHint(state) {
  const missing = missingContactFields(state);
  if (!missing.length) return '聯絡資料已齊全，我們可以繼續整理課程資料。';

  return [
    '目前還需要先補這幾項聯絡資料：',
    ...missing.map((field, index) => `${index + 1}. ${field.label}${field.reason ? `（${field.reason}）` : ''}`),
    '',
    '補齊後我再帶你進入課程資料整理。',
  ].join('\n');
}

function contactFallbackForState(state) {
  const pool = [
    '我先拉回聯絡資料這邊 😊\n目前需要先補姓名、Email、LINE ID Link，補齊後才會進入課程資料整理。',
    '這邊我還沒拿到完整聯絡資料 📝\n可以先提供姓名、Email、LINE ID Link 嗎？Email 之後會作為登入帳號。',
    '我先不往課程資料跳，避免建檔資料不完整。\n目前請先補：姓名、Email、LINE ID Link。',
    '沒關係，我們一步一步來 😊\n先把聯絡資料補齊：姓名、Email、LINE ID Link。',
    '這句我先不當作資料欄位喔。\n目前必填的是姓名、Email、LINE ID Link，補完才會繼續。',
  ];
  let index = Math.floor(Math.random() * pool.length);
  const fallbackId = `contact_required_${index}`;
  if (pool.length > 1 && fallbackId === state.last_fallback_id) index = (index + 1) % pool.length;
  state.last_fallback_type = 'contact_required';
  state.last_fallback_id = `contact_required_${index}`;
  state.last_fallback_key = state.last_fallback_id;
  return `${pool[index]}\n\n${contactMissingHint(state)}`;
}

function handleEmailDeclineText(state, text) {
  if (!missingContactFields(state).some((field) => field.key === 'email')) return false;
  if (!/(不想|不方便|不要|不提供|先不要|先不給|沒有).*email|email.*(不想|不方便|不要|不提供|先不要|先不給|沒有)|沒有信箱|沒有電子信箱/i.test(String(text || ''))) return false;

  state.email_decline_count = (state.email_decline_count || 0) + 1;
  if (state.email_decline_count >= 2) {
    state.email_status = 'declined';
    state.needs_human_contact_review = true;
  }
  return true;
}

function isClearlyInvalidContactReply(text) {
  const normalized = String(text || '').trim();
  return !normalized
    || isConfirmationLike(normalized)
    || /^(哈+|呵+|嘿+|嗯+|喔+|哦+|好喔|隨便|不知道|不確定|測試|test|123+|abc+)$/i.test(normalized)
    || /^[0-9\s\p{P}\p{S}_]+$/u.test(normalized);
}

function isPlausibleUserName(value, email) {
  const text = String(value || '').trim();
  if (!text || text === email) return false;
  if (isClearlyInvalidContactReply(text)) return false;
  if (isCourseAnswerToken(text)) return false;
  if (isShortLineCodeLike(text)) return false;
  if (/@/.test(text) || /line|http|\.me|\.ee/i.test(text)) return false;
  if (text.length > 30) return false;
  return /[\p{Script=Han}A-Za-z]/u.test(text);
}

async function handleCourseStep(eventData, state, env) {
  const text = eventData.user_message;

  if (isFieldHelpQuestion(text)) {
    applyFormFields(state, text);
    applyInlineCourseFieldValues(state, text);
    refreshCollectedData(state);
    state.invalid_reply_count = 0;
    await saveIntakeState(eventData.user_id, state, env);
    return fieldHelpReply(state, text);
  }

  applyFormFields(state, text);
  applyCourseTextByStep(state, text);
  refreshCollectedData(state);

  moveToNextIncompleteStep(state);

  const missing = isCourseStep(state.current_intake_step)
    ? missingCourseFieldsForCurrentStep(state)
    : [];

  if (isCourseStep(state.current_intake_step) && missing.length) {
    const hasAnyUseful = hasCourseUsefulSignal(text) || looksLikeForm(text);

    if (!hasAnyUseful) {
      state.invalid_reply_count += 1;
      const reply = fallbackForState(state, classifyInvalidText(text));
      await saveIntakeState(eventData.user_id, state, env);
      return reply;
    }

    state.invalid_reply_count = 0;
    await saveIntakeState(eventData.user_id, state, env);
    return nextCoursePrompt(state);
  }

  state.invalid_reply_count = 0;

  if (isCourseStep(state.current_intake_step)) {
    await saveIntakeState(eventData.user_id, state, env);
    return nextCoursePrompt(state);
  }

  state.current_intake_step = 'collecting_hero_artwork_images';
  state.current_required_fields = ['hero_artwork_images'];
  markCopyrightNoticeShown(state, 'collecting_hero_artwork_images');
  await saveIntakeState(eventData.user_id, state, env);
  return heroArtworkPrompt();
}

function applyCourseTextByStep(state, text) {
  const value = String(text || '').trim();

  if (!value) return;

  if (state.current_intake_step === 'collecting_course_name_type') {
    if (!state.course_name && !looksLikeForm(value)) {
      const named = value.match(/(?:課程名稱|課名)\s*[:：]?\s*([^\n，,。]+)/i)?.[1] || '';
      if (named) {
        state.course_name = cleanValue(named);
      } else if (isGenericCourseName(value)) {
        state.suggested_course_name = cleanValue(value);
        state.course_name_needs_confirmation = true;
      } else {
        state.course_name = cleanValue(value);
      }
    }

    if (!state.course_type) {
      const typed = value.match(/(?:課程類型|類型)\s*[:：]?\s*([^\n，,。]+)/i)?.[1] || '';
      state.course_type = cleanValue(typed || inferCourseType(value));
    }
  }

  if (state.current_intake_step === 'collecting_course_format_location') {
    if (!state.course_format) {
      const format = value.match(/實體|線上|混合/i)?.[0] || '';
      if (format) state.course_format = format;
      else if (isUnknownLike(value)) state.course_format = '未定';
    }

    if (!state.course_location && state.last_asked_field === 'course_location' && isUnknownLike(value)) {
      state.course_location = '未定';
    }

    if (!state.course_location && /實體|混合/i.test(state.course_format || '') && !/實體|線上|混合/i.test(value)) {
      state.course_location = cleanValue(value);
    }
  }

  if (state.current_intake_step === 'collecting_course_schedule') {
    if (!state.expected_launch_date && state.last_asked_field === 'expected_launch_date') {
      state.expected_launch_date = cleanValue(value);
      state.expected_launch_date_status = classifyDateStatus(value);
    } else if (!state.expected_start_date && state.last_asked_field === 'expected_start_date') {
      state.expected_start_date = cleanValue(value);
      state.expected_start_date_status = classifyDateStatus(value);
    } else {
      const launch = value.match(/(?:預計招生時間|招生時間|開放報名)\s*[:：]?\s*([^\n，,。]+)/i)?.[1] || '';
      const start = value.match(/(?:預計開課時間|開課時間|課程日期|上課日期)\s*[:：]?\s*([^\n，,。]+)/i)?.[1] || '';
      if (launch) {
        state.expected_launch_date = cleanValue(launch);
        state.expected_launch_date_status = classifyDateStatus(launch);
      }
      if (start) {
        state.expected_start_date = cleanValue(start);
        state.expected_start_date_status = classifyDateStatus(start);
      }
      if (!launch && !start && !state.expected_launch_date) {
        state.expected_launch_date = cleanValue(value);
        state.expected_launch_date_status = classifyDateStatus(value);
      } else if (!launch && !start && !state.expected_start_date) {
        state.expected_start_date = cleanValue(value);
        state.expected_start_date_status = classifyDateStatus(value);
      }
    }
  }

  if (state.current_intake_step === 'collecting_course_sales') {
    if (!state.course_capacity && state.last_asked_field === 'course_capacity') {
      state.course_capacity = cleanValue(value);
      state.course_capacity_status = classifyUnknownStatus(value);
    } else if (!state.course_price && state.last_asked_field === 'course_price') {
      state.course_price = cleanValue(value);
      state.course_price_status = classifyUnknownStatus(value);
    } else {
      const capacity = value.match(/(?:課程名額|名額|人數)\s*[:：]?\s*([^\n，,。]+)/i)?.[1] || value.match(/\d+\s*(?:人|位|名)/)?.[0] || '';
      const price = value.match(/(?:課程費用|費用|價格)\s*[:：]?\s*([^\n，,。]+)/i)?.[1] || '';
      if (capacity && !state.course_capacity) {
        state.course_capacity = cleanValue(capacity);
        state.course_capacity_status = classifyUnknownStatus(capacity);
      }
      if (price && !state.course_price) {
        state.course_price = cleanValue(price);
        state.course_price_status = classifyUnknownStatus(price);
      }
    }
  }

  if (state.current_intake_step === 'collecting_course_content') {
    if (!state.target_audience && state.last_asked_field === 'target_audience') state.target_audience = cleanValue(value);
    else if (!state.course_features && state.last_asked_field === 'course_features') state.course_features = cleanValue(value);
    else if (!state.after_class_support && state.last_asked_field === 'after_class_support') state.after_class_support = cleanValue(value);
    else {
      const audience = value.match(/(?:適合對象|對象|招生對象)\s*[:：]?\s*([^\n，,。]+)/i)?.[1] || '';
      const features = value.match(/(?:課程特色|特色|亮點)\s*[:：]?\s*([^\n]+)/i)?.[1] || '';
      const support = value.match(/(?:課後支援|課後服務|課後)\s*[:：]?\s*([^\n，,。]+)/i)?.[1] || '';
      if (audience) state.target_audience = cleanValue(audience);
      if (features) state.course_features = cleanValue(features);
      if (support) state.after_class_support = cleanValue(support);
    }
  }
}

function applyInlineCourseFieldValues(state, text) {
  const value = String(text || '').trim();
  if (!value) return;

  const courseName = value.match(/課程名稱\s*[:：]?\s*(.+?)(?=\s*(?:課程類型|類型|課程形式|上課形式|上課地點|預計招生時間|預計開課時間|課程名額|課程費用|適合對象|課程特色|課後支援|$))/i)?.[1] || '';
  if (courseName && !state.course_name && !isFieldHelpQuestion(courseName)) {
    state.course_name = cleanValue(courseName);
  }

  const courseType = value.match(/(?:課程類型|類型)\s*[:：]?\s*(.+?)(?=\s*(?:課程名稱|課程形式|上課形式|上課地點|預計招生時間|預計開課時間|課程名額|課程費用|適合對象|課程特色|課後支援|$))/i)?.[1] || '';
  if (courseType && !state.course_type && !isFieldHelpQuestion(courseType)) {
    state.course_type = cleanValue(courseType);
  }
}

function fieldHelpReply(state, text) {
  if (/課程類型|類型/i.test(String(text || ''))) {
    return [
      '課程類型是指這堂課的「主題分類」，不是正式課名 😊',
      '它會幫我們判斷招生頁適合用什麼內容和視覺方向。',
      '',
      '例如可以填：色鉛筆、畫畫、水彩、手作、花藝、美甲、攝影、設計、瑜伽、烘焙。',
      '',
      state.course_name ? `我已先記下課程名稱：${state.course_name}` : '',
      '如果你的課是色鉛筆，可以直接回覆：',
      '課程類型：色鉛筆',
    ].filter(Boolean).join('\n');
  }

  if (/課程形式|上課形式/i.test(String(text || ''))) {
    return '課程形式是指上課方式：實體、線上，或混合。\n如果還不確定，也可以先回「還沒確定」。';
  }

  if (/LINE ID|LINE Link|LINE ID Link/i.test(String(text || ''))) {
    return 'LINE ID Link 是可用來後續聯繫與通知的 LINE 連結，例如 line.me 或 lin.ee 開頭的連結。\n如果現在不確定，可以先貼你手邊的 LINE 聯絡資訊，我會標記後續確認。';
  }

  return [
    '可以，我先說明一下這個欄位怎麼填 😊',
    currentStepHint(state),
  ].join('\n');
}

function applyFormFields(state, text) {
  const lines = String(text || '').replace(/\r\n/g, '\n').split('\n');

  for (const line of lines) {
    const match = line.match(/^\s*(?:\d+\.\s*)?([^:：]+)\s*[:：]\s*(.*)$/);
    if (!match) continue;

    const label = normalizeLabel(match[1]);
    const value = normalizeFormValue(match[2]);
    if (!value) continue;

    const key = fieldKeyByLabel(label);
    if (!key) continue;

    setFieldValue(state, key, value);
  }
}

function setFieldValue(state, key, value) {
  if (key === 'email') {
    state.email = value;
    state.email_status = isValidEmail(value) ? 'valid' : 'invalid';
    return;
  }

  if (key === 'line_id_link') {
    state.line_id_link = value;
    state.line_id_link_status = classifyLineIdLink(value);
    return;
  }

  if (key === 'course_capacity') {
    state.course_capacity = value;
    state.course_capacity_status = classifyUnknownStatus(value);
    return;
  }

  if (key === 'course_price') {
    state.course_price = value;
    state.course_price_status = classifyUnknownStatus(value);
    return;
  }

  if (key === 'expected_launch_date') {
    state.expected_launch_date = value;
    state.expected_launch_date_status = classifyDateStatus(value);
    return;
  }

  if (key === 'expected_start_date') {
    state.expected_start_date = value;
    state.expected_start_date_status = classifyDateStatus(value);
    return;
  }

  state[key] = value;
}

function fieldKeyByLabel(label) {
  const map = {
    '姓名': 'user_name',
    '使用者姓名': 'user_name',
    'email': 'email',
    'e-mail': 'email',
    '信箱': 'email',
    '電子信箱': 'email',
    'lineidlink': 'line_id_link',
    'lineid的link': 'line_id_link',
    'linelink': 'line_id_link',
    'lineid': 'line_id_link',
    '課程名稱': 'course_name',
    '課名': 'course_name',
    '課程類型': 'course_type',
    '類型': 'course_type',
    '課程形式': 'course_format',
    '上課形式': 'course_format',
    '上課地點': 'course_location',
    '地點': 'course_location',
    '預計招生時間': 'expected_launch_date',
    '招生時間': 'expected_launch_date',
    '預計開課時間': 'expected_start_date',
    '開課時間': 'expected_start_date',
    '課程日期': 'expected_start_date',
    '課程名額': 'course_capacity',
    '名額': 'course_capacity',
    '人數': 'course_capacity',
    '課程費用': 'course_price',
    '費用': 'course_price',
    '價格': 'course_price',
    '適合對象': 'target_audience',
    '對象': 'target_audience',
    '課程特色': 'course_features',
    '特色': 'course_features',
    '課後支援': 'after_class_support',
    '課後服務': 'after_class_support',
  };

  return map[label] || '';
}

function nextCoursePrompt(state, prefix = '') {
  const lines = prefix ? [prefix, ''] : [];

  if (state.current_intake_step === 'collecting_course_name_type') {
    if (state.course_name_needs_confirmation && !state.course_name) {
      state.last_asked_field = 'course_name';
      return [
        ...lines,
        `我先把課程類型記成「${state.course_type || inferCourseType(state.suggested_course_name) || '未定'}」。`,
        `課程名稱要暫定為「${state.suggested_course_name}」嗎？還是有正式名稱？`,
      ].join('\n');
    }

    const missing = missingCourseFieldsForCurrentStep(state);
    if (!missing.length) {
      moveToNextIncompleteStep(state);
      return nextCoursePrompt(state, prefix);
    }
    state.last_asked_field = missing[0]?.key || '';
    return [...lines, `接下來想確認：${missing.map((field) => field.label).join('、')}。\n可以簡單回覆就好。`].join('\n');
  }

  if (state.current_intake_step === 'collecting_course_format_location') {
    if (!state.course_format) {
      state.last_asked_field = 'course_format';
      return [...lines, '這堂課是實體、線上，還是混合上課呢？'].join('\n');
    }
    state.last_asked_field = 'course_location';
    return [...lines, '上課地點大概在哪裡呢？如果是線上課，可以回覆「線上」。'].join('\n');
  }

  if (state.current_intake_step === 'collecting_course_schedule') {
    if (!state.expected_launch_date) {
      state.last_asked_field = 'expected_launch_date';
      return [...lines, '預計什麼時候開始招生或開放報名呢？'].join('\n');
    }
    state.last_asked_field = 'expected_start_date';
    return [...lines, '預計什麼時候開課呢？可以先提供大約日期或月份。'].join('\n');
  }

  if (state.current_intake_step === 'collecting_course_sales') {
    if (!state.course_capacity) {
      state.last_asked_field = 'course_capacity';
      return [...lines, '這堂課預計招收幾位學生呢？如果還不確定，可以回覆「未定」。'].join('\n');
    }
    state.last_asked_field = 'course_price';
    return [...lines, '課程對外招生費用是多少呢？如果還不確定，可以回覆「未定」。'].join('\n');
  }

  if (state.current_intake_step === 'collecting_course_content') {
    if (!state.target_audience) {
      state.last_asked_field = 'target_audience';
      return [...lines, '這堂課主要適合哪些對象呢？'].join('\n');
    }
    if (!state.course_features) {
      state.last_asked_field = 'course_features';
      return [...lines, '這堂課最想強調的特色或亮點是什麼？'].join('\n');
    }
    state.last_asked_field = 'after_class_support';
    return [...lines, '課後會有什麼支援嗎？例如社群、回放、作業批改、講義或諮詢；沒有也可以回「目前沒有」。'].join('\n');
  }

  return '課程資料已收到，接下來整理照片素材。';
}

function missingCourseFieldsForCurrentStep(state) {
  if (state.current_intake_step === 'collecting_course_name_type') {
    return [
      !state.course_name ? { key: 'course_name', label: '課程名稱' } : null,
      !state.course_type ? { key: 'course_type', label: '課程類型' } : null,
    ].filter(Boolean);
  }

  if (state.current_intake_step === 'collecting_course_format_location') {
    return [
      !state.course_format ? { key: 'course_format', label: '課程形式' } : null,
      /實體|混合/i.test(state.course_format || '') && !state.course_location ? { key: 'course_location', label: '上課地點' } : null,
    ].filter(Boolean);
  }

  if (state.current_intake_step === 'collecting_course_schedule') {
    return [
      !state.expected_launch_date ? { key: 'expected_launch_date', label: '預計招生時間' } : null,
      !state.expected_start_date ? { key: 'expected_start_date', label: '預計開課時間' } : null,
    ].filter(Boolean);
  }

  if (state.current_intake_step === 'collecting_course_sales') {
    return [
      !state.course_capacity ? { key: 'course_capacity', label: '課程名額' } : null,
      !state.course_price ? { key: 'course_price', label: '課程費用' } : null,
    ].filter(Boolean);
  }

  if (state.current_intake_step === 'collecting_course_content') {
    return [
      !state.target_audience ? { key: 'target_audience', label: '適合對象' } : null,
      !state.course_features ? { key: 'course_features', label: '課程特色' } : null,
      !state.after_class_support ? { key: 'after_class_support', label: '課後支援' } : null,
    ].filter(Boolean);
  }

  return [];
}

function advanceCourseStep(state) {
  const order = [
    'collecting_course_name_type',
    'collecting_course_format_location',
    'collecting_course_schedule',
    'collecting_course_sales',
    'collecting_course_content',
  ];
  const index = order.indexOf(state.current_intake_step);
  state.current_intake_step = order[index + 1] || 'collecting_hero_artwork_images';
  state.current_required_fields = missingCourseFieldsForCurrentStep(state).map((field) => field.key);
  state.missing_fields = allMissingRequiredFields(state).map((field) => field.key);
  state.last_asked_field = '';
}

function moveToNextIncompleteStep(state) {
  const steps = [
    'collecting_course_name_type',
    'collecting_course_format_location',
    'collecting_course_schedule',
    'collecting_course_sales',
    'collecting_course_content',
  ];

  for (const step of steps) {
    state.current_intake_step = step;
    const missing = missingCourseFieldsForCurrentStep(state);
    if (missing.length) {
      state.current_required_fields = missing.map((field) => field.key);
      state.missing_fields = allMissingRequiredFields(state).map((field) => field.key);
      return;
    }
  }

  state.current_intake_step = 'collecting_hero_artwork_images';
  state.current_required_fields = ['hero_artwork_images'];
  state.missing_fields = allMissingRequiredFields(state).map((field) => field.key);
  state.last_asked_field = '';
}

function heroArtworkPrompt() {
  return photoPrompt({
    title: '第 1 階段｜作品主圖',
    ask: '請先提供 1～3 張最想放在招生頁第一眼看到的作品照片。',
    uses: ['招生頁主視覺', '課程作品展示', '三款樣板提案時判斷整體風格'],
    tips: ['作品要清楚', '光線明亮', '背景不要太亂', '最好能代表這堂課成果'],
  });
}

function photoPrompt(config) {
  return [
    copyrightNotice(),
    '',
    `【${config.title}】`,
    '',
    config.ask,
    '',
    '這些照片會用在：',
    ...config.uses.map((item, index) => `${index + 1}. ${item}`),
    '',
    '照片建議：',
    ...config.tips.map((item) => `- ${item}`),
    '',
    '如果目前沒有，也可以回覆「目前沒有」，我會先幫你記錄並進入下一階段。',
  ].join('\n');
}

async function handlePhotoText(eventData, state, env) {
  const text = eventData.user_message;

  if (isNoPhotoReply(text)) {
    markPhotoMissingForCurrentStep(state);
    const reply = advancePhotoStepAndBuildReply(state);
    state.invalid_reply_count = 0;
    await saveIntakeState(eventData.user_id, state, env);
    return reply;
  }

  if (/分類|多張|哪幾張|你幫我先分類看看/i.test(text)) {
    setCurrentPhotoStatus(state, 'need_review');
    await saveIntakeState(eventData.user_id, state, env);
    return multiPhotoClassificationPrompt();
  }

  state.invalid_reply_count += 1;
  const reply = fallbackForState(state, classifyInvalidText(text));
  await saveIntakeState(eventData.user_id, state, env);
  return reply;
}

async function handlePhotoUpload(eventData, state, env) {
  const field = photoFieldForStep(state.current_intake_step);
  if (!field) return '';

  markCopyrightNoticeShown(state, state.current_intake_step);
  const images = Array.isArray(state[field]) ? state[field] : [];
  const stored = await saveLineImageToStorage(eventData, field, env);

  images.push({
    attachment_id: eventData.message_id,
    file_url: stored.file_url || '',
    storage_key: stored.storage_key || '',
    original_filename: stored.original_filename || '',
    uploaded_at: new Date().toISOString(),
    image_purpose: imagePurposeForField(field),
    notes: stored.ok ? '照片已收到；目前無法自動判斷圖片內容是否完全符合此區塊，後續會確認用途。' : '照片已收到，但素材儲存需後續確認。',
  });

  state[field] = images;
  state[`${field}_status`] = 'need_review';
  state.invalid_reply_count = 0;

  const reply = advancePhotoStepAndBuildReply(state);
  await saveIntakeState(eventData.user_id, state, env);
  return reply;
}

function advancePhotoStepAndBuildReply(state) {
  if (state.current_intake_step === 'collecting_hero_artwork_images') {
    markCopyrightNoticeShown(state, 'collecting_detail_artwork_images');
    state.current_intake_step = 'collecting_detail_artwork_images';
    state.current_required_fields = ['detail_artwork_images'];
    return photoPrompt({
      title: '第 2 階段｜作品細節圖',
      ask: '請提供 2～5 張比較近拍、可以看出筆觸或細節的照片。',
      uses: ['作品展示區', '課程細節介紹', '呈現作品質感與完成度'],
      tips: ['可以拍作品局部', '看得到細節或色彩層次', '光線清楚'],
    });
  }

  if (state.current_intake_step === 'collecting_detail_artwork_images') {
    markCopyrightNoticeShown(state, 'collecting_teacher_images');
    state.current_intake_step = 'collecting_teacher_images';
    state.current_required_fields = ['teacher_images'];
    return photoPrompt({
      title: '第 3 階段｜老師照片',
      ask: '請提供 1～2 張老師照片。',
      uses: ['講師介紹區', '增加學生對課程的信任感', '讓招生頁看起來更有溫度'],
      tips: ['自然半身照', '創作或教學中的照片', '光線清楚、表情自然'],
    });
  }

  if (state.current_intake_step === 'collecting_teacher_images') {
    markCopyrightNoticeShown(state, 'collecting_classroom_images');
    state.current_intake_step = 'collecting_classroom_images';
    state.current_required_fields = ['classroom_images'];
    return photoPrompt({
      title: '第 4 階段｜教室 / 上課空間照片',
      ask: '請提供 1～3 張教室、工作室或上課空間照片。',
      uses: ['上課環境介紹區', '呈現實體課空間氛圍', '增加安心感與真實感'],
      tips: ['空間盡量明亮', '桌面稍微整理', '可拍教室一角、座位區或作品展示區'],
    });
  }

  if (state.current_intake_step === 'collecting_classroom_images') {
    markCopyrightNoticeShown(state, 'collecting_class_activity_images');
    state.current_intake_step = 'collecting_class_activity_images';
    state.current_required_fields = ['class_activity_images'];
    return photoPrompt({
      title: '第 5 階段｜上課過程照片',
      ask: '請提供 2～5 張上課過程或課堂氛圍照片。',
      uses: ['課堂氛圍區', '呈現老師示範與學生創作', '讓學生更容易想像實際上課情境'],
      tips: ['老師示範', '學生手部創作', '桌面材料或作品進行中畫面', '若有人臉，請先確認同意使用'],
    });
  }

  state.current_intake_step = 'ready_for_confirmation';
  state.current_required_fields = [];
  return buildIntakeSummary(state);
}

function markPhotoMissingForCurrentStep(state) {
  const field = photoFieldForStep(state.current_intake_step);
  if (!field) return;

  markCopyrightNoticeShown(state, state.current_intake_step);
  state[field] = [];

  if (field === 'hero_artwork_images') {
    state.hero_artwork_images_status = 'missing';
    state.hero_artwork_images_note = '客戶目前沒有作品主圖。此素材會影響招生頁主視覺與三款樣板提案效果，後續可先用老師照片、材料照片或暫定視覺圖補位。';
    return;
  }

  const notes = {
    detail_artwork_images: '客戶目前沒有作品細節圖，後續可用作品主圖裁切、材料照片或其他作品照片補位。',
    teacher_images: '客戶目前沒有老師照片，後續講師介紹區可改用文字介紹、作品圖、品牌圖或教學相關素材補位。',
    classroom_images: '客戶目前沒有教室 / 上課空間照片，後續版型不強制顯示上課環境區，可改用作品圖、材料圖、老師照片或課程完成品示意圖補位。',
    class_activity_images: '客戶目前沒有上課過程照片，後續課堂氛圍區可改用作品圖、材料圖、老師示範照或文字說明補位。',
  };

  state[`${field}_status`] = 'none';
  state[`${field}_note`] = notes[field] || '客戶目前沒有此階段照片。';
}

function buildIntakeSummary(state) {
  return [
    '我幫你整理一下目前資料，請確認是否正確：',
    '',
    `使用者姓名：${valueOrEmpty(state.user_name)}`,
    `Email：${valueOrEmpty(state.email)}`,
    `LINE ID Link：${valueOrEmpty(state.line_id_link)}`,
    `課程名稱：${valueOrEmpty(state.course_name)}`,
    `課程類型：${valueOrEmpty(state.course_type)}`,
    `課程形式：${valueOrEmpty(state.course_format)}`,
    `上課地點：${valueOrEmpty(state.course_location)}`,
    `預計招生時間：${valueOrEmpty(state.expected_launch_date)}`,
    `預計開課時間：${valueOrEmpty(state.expected_start_date)}`,
    `課程名額：${valueOrEmpty(state.course_capacity)}`,
    `課程費用：${valueOrEmpty(state.course_price)}`,
    `適合對象：${valueOrEmpty(state.target_audience)}`,
    `課程特色：${valueOrEmpty(state.course_features)}`,
    `課後支援：${valueOrEmpty(state.after_class_support)}`,
    `作品主圖：${state.hero_artwork_images_status}`,
    `作品細節圖：${state.detail_artwork_images_status}`,
    `老師照片：${state.teacher_images_status}`,
    `教室 / 上課空間照片：${state.classroom_images_status}`,
    `上課過程照片：${state.class_activity_images_status}`,
    '',
    '以上資料是否正確？如果正確，請回覆「確認」。如果需要修改，也可以直接告訴我。',
  ].join('\n');
}

async function handleConfirmationStep(eventData, state, env) {
  const text = eventData.user_message;

  if (!isExplicitConfirmation(text)) {
    await saveIntakeState(eventData.user_id, state, env);
    return '可以，想修改哪一項可以直接告訴我。\n如果資料都正確，請明確回覆「確認」，我才會送出建檔。';
  }

  const missing = allMissingRequiredFields(state);

  if (missing.length) {
    state.current_intake_step = stepForMissingField(missing[0].key);
    state.current_required_fields = missingCourseFieldsForCurrentStep(state).map((field) => field.key);
    if (state.current_intake_step === 'collecting_required_contact') {
      state.current_required_fields = missingContactFields(state).map((field) => field.key);
    }
    state.missing_fields = missing.map((field) => field.key);
    state.intake_status = 'in_progress';
    await saveIntakeState(eventData.user_id, state, env);
    return [
      '目前還有必要資料沒有補齊，所以不能先建檔。',
      `還缺：${missing.map((field) => field.label).join('、')}`,
      '',
      currentStepHint(state),
    ].join('\n');
  }

  state.confirmed_by_client = true;
  state.confirmed_at = new Date().toISOString();
  state.intake_status = 'confirmed';
  const payload = buildConfirmedPayload(state);
  const createResult = await createClientRecord(payload, env);

  if (!createResult.ok) {
    state.intake_status = 'confirmed_pending_sync';
    await saveIntakeState(eventData.user_id, state, env);
    return [
      '資料已確認，我也已整理成標準 JSON。',
      '不過目前後端建檔 API 尚未成功寫入，我會先保留資料，避免你重新填一次。',
      '',
      '請稍候我們確認後再通知你下一步。',
    ].join('\n');
  }

  await clearIntakeState(eventData.user_id, env);
  return [
    '好的，資料已確認並送出建檔。',
    createResult.record_id ? `案件編號：${createResult.record_id}` : '',
    '',
    '接下來會依照這份資料準備招生頁視覺提案，完成後再透過 LINE 通知你確認。',
  ].filter(Boolean).join('\n');
}

function buildConfirmedPayload(state) {
  return {
    event_type: 'client_intake_confirmed',
    client: {
      user_name: state.user_name,
      email: state.email,
      email_status: state.email_status,
      line_id_link: state.line_id_link,
      line_id_link_status: state.line_id_link_status,
      needs_human_contact_review: state.needs_human_contact_review === true || state.line_id_link_status === 'need_review',
    },
    course_project: {
      course_name: state.course_name,
      course_type: state.course_type,
      course_format: state.course_format,
      course_location: state.course_location,
      expected_launch_date: state.expected_launch_date,
      expected_launch_date_status: state.expected_launch_date_status,
      expected_start_date: state.expected_start_date,
      expected_start_date_status: state.expected_start_date_status,
      course_capacity: state.course_capacity,
      course_capacity_status: state.course_capacity_status,
      course_price: state.course_price,
      course_price_status: state.course_price_status,
      target_audience: state.target_audience,
      course_features: splitFeatures(state.course_features),
      after_class_support: state.after_class_support,
      has_photos: hasAnyPhotos(state) ? 'yes' : 'no',
      has_copywriting: state.has_copywriting || 'pending',
      needs_template_proposal: state.needs_template_proposal !== false,
    },
    assets: {
      hero_artwork_images: state.hero_artwork_images,
      hero_artwork_images_status: state.hero_artwork_images_status,
      hero_artwork_images_note: state.hero_artwork_images_note,
      detail_artwork_images: state.detail_artwork_images,
      detail_artwork_images_status: state.detail_artwork_images_status,
      detail_artwork_images_note: state.detail_artwork_images_note,
      teacher_images: state.teacher_images,
      teacher_images_status: state.teacher_images_status,
      teacher_images_note: state.teacher_images_note,
      classroom_images: state.classroom_images,
      classroom_images_status: state.classroom_images_status,
      classroom_images_note: state.classroom_images_note,
      class_activity_images: state.class_activity_images,
      class_activity_images_status: state.class_activity_images_status,
      class_activity_images_note: state.class_activity_images_note,
      copyright_notice_flags: {
        hero_artwork_copyright_notice_shown: state.hero_artwork_copyright_notice_shown === true,
        detail_artwork_copyright_notice_shown: state.detail_artwork_copyright_notice_shown === true,
        teacher_photo_copyright_notice_shown: state.teacher_photo_copyright_notice_shown === true,
        classroom_photo_copyright_notice_shown: state.classroom_photo_copyright_notice_shown === true,
        class_activity_photo_copyright_notice_shown: state.class_activity_photo_copyright_notice_shown === true,
      },
    },
    confirmed_at: state.confirmed_at || new Date().toISOString(),
    source_channel: 'line',
    line_user_id: state.user_id,
  };
}

function hasAnyPhotos(state) {
  return [
    state.hero_artwork_images,
    state.detail_artwork_images,
    state.teacher_images,
    state.classroom_images,
    state.class_activity_images,
  ].some((items) => Array.isArray(items) && items.length > 0);
}

async function createClientRecord(payload, env) {
  if (!env.ADMISSION_API_URL) {
    console.log('Admission API not configured. Payload:', JSON.stringify(payload));
    return { ok: true, record_id: 'pending_admission_api' };
  }

  try {
    const headers = { 'Content-Type': 'application/json' };
    if (env.ADMISSION_API_KEY) {
      headers.Authorization = `Bearer ${env.ADMISSION_API_KEY}`;
      headers['X-Admission-Api-Key'] = env.ADMISSION_API_KEY;
    }

    const response = await fetch(env.ADMISSION_API_URL, {
      method: 'POST',
      headers,
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      console.log('Admission API failed', response.status, await response.text());
      return { ok: false, error: `admission_api_${response.status}` };
    }

    const data = await response.json().catch(() => ({}));
    return { ok: true, record_id: data.record_id || data.id || '' };
  } catch (error) {
    console.log('Admission API exception', error?.message || error);
    return { ok: false, error: 'admission_api_exception' };
  }
}

function missingContactFields(state) {
  return [
    !state.user_name ? { key: 'user_name', label: '姓名', reason: '建立客戶資料與通知稱呼' } : null,
    !state.email || state.email_status !== 'valid' ? { key: 'email', label: 'Email', reason: '未來會作為登入帳號' } : null,
    !state.line_id_link || state.line_id_link_status === 'invalid_display_name_only'
      ? { key: 'line_id_link', label: 'LINE ID Link', reason: '用於後續通知與聯繫' }
      : null,
  ].filter(Boolean);
}

function allMissingRequiredFields(state) {
  return [
    ...missingContactFields(state),
    !state.course_name ? { key: 'course_name', label: '課程名稱' } : null,
    !state.course_type ? { key: 'course_type', label: '課程類型' } : null,
    !state.course_format ? { key: 'course_format', label: '課程形式' } : null,
    /實體|混合/i.test(state.course_format || '') && !state.course_location ? { key: 'course_location', label: '上課地點' } : null,
    !state.expected_launch_date ? { key: 'expected_launch_date', label: '預計招生時間' } : null,
    !state.expected_start_date ? { key: 'expected_start_date', label: '預計開課時間' } : null,
    !state.course_capacity ? { key: 'course_capacity', label: '課程名額' } : null,
    !state.course_price ? { key: 'course_price', label: '課程費用' } : null,
    !state.target_audience ? { key: 'target_audience', label: '適合對象' } : null,
    !state.course_features ? { key: 'course_features', label: '課程特色' } : null,
    !state.after_class_support ? { key: 'after_class_support', label: '課後支援' } : null,
  ].filter(Boolean);
}

function stepForMissingField(key) {
  if (['user_name', 'email', 'line_id_link'].includes(key)) return 'collecting_required_contact';
  if (['course_name', 'course_type'].includes(key)) return 'collecting_course_name_type';
  if (['course_format', 'course_location'].includes(key)) return 'collecting_course_format_location';
  if (['expected_launch_date', 'expected_start_date'].includes(key)) return 'collecting_course_schedule';
  if (['course_capacity', 'course_price'].includes(key)) return 'collecting_course_sales';
  return 'collecting_course_content';
}

function currentStepHint(state) {
  if (state.current_intake_step === 'collecting_required_contact') return '請先補姓名、Email、LINE ID Link。';
  if (isCourseStep(state.current_intake_step)) return nextCoursePrompt(state);
  if (isPhotoStep(state.current_intake_step)) return '請依照目前照片階段提供照片，或回覆「目前沒有」。';
  return '我們可以繼續整理課程招生頁資料。';
}

function refreshCollectedData(state) {
  state.collected_data = {
    user_name: state.user_name,
    email: state.email,
    line_id_link: state.line_id_link,
    course_name: state.course_name,
    course_type: state.course_type,
    course_format: state.course_format,
    course_location: state.course_location,
    expected_launch_date: state.expected_launch_date,
    expected_start_date: state.expected_start_date,
    course_capacity: state.course_capacity,
    course_price: state.course_price,
    target_audience: state.target_audience,
    course_features: state.course_features,
    after_class_support: state.after_class_support,
  };
  state.missing_fields = allMissingRequiredFields(state).map((field) => field.key);
}

function isCourseStep(step) {
  return [
    'collecting_course_name_type',
    'collecting_course_format_location',
    'collecting_course_schedule',
    'collecting_course_sales',
    'collecting_course_content',
  ].includes(step);
}

function isPhotoStep(step) {
  return [
    'collecting_hero_artwork_images',
    'collecting_detail_artwork_images',
    'collecting_teacher_images',
    'collecting_classroom_images',
    'collecting_class_activity_images',
  ].includes(step);
}

function photoFieldForStep(step) {
  return {
    collecting_hero_artwork_images: 'hero_artwork_images',
    collecting_detail_artwork_images: 'detail_artwork_images',
    collecting_teacher_images: 'teacher_images',
    collecting_classroom_images: 'classroom_images',
    collecting_class_activity_images: 'class_activity_images',
  }[step] || '';
}

function imagePurposeForField(field) {
  return {
    hero_artwork_images: 'hero_artwork',
    detail_artwork_images: 'detail_artwork',
    teacher_images: 'teacher',
    classroom_images: 'classroom',
    class_activity_images: 'class_activity',
  }[field] || field;
}

function markCopyrightNoticeShown(state, step) {
  const key = {
    collecting_hero_artwork_images: 'hero_artwork_copyright_notice_shown',
    collecting_detail_artwork_images: 'detail_artwork_copyright_notice_shown',
    collecting_teacher_images: 'teacher_photo_copyright_notice_shown',
    collecting_classroom_images: 'classroom_photo_copyright_notice_shown',
    collecting_class_activity_images: 'class_activity_photo_copyright_notice_shown',
  }[step];
  if (key) state[key] = true;
}

function setCurrentPhotoStatus(state, status) {
  const field = photoFieldForStep(state.current_intake_step);
  if (field) state[`${field}_status`] = status;
}

function multiPhotoClassificationPrompt() {
  return [
    '我收到多張照片囉 😊🖼️',
    '為了後續放在招生頁時比較準確，想請你幫我簡單分一下：',
    '',
    '1. 哪幾張是作品主圖？',
    '2. 哪幾張是作品細節圖？',
    '3. 哪幾張是老師照片？',
    '4. 哪幾張是教室 / 上課空間照片？',
    '5. 哪幾張是上課過程照片？',
    '',
    '如果不確定，也可以直接告訴我：「你幫我先分類看看」，我會先暫時分類，後續再請你確認。',
  ].join('\n');
}

async function saveLineImageToStorage(eventData, imagePurpose, env) {
  if (!env.ADMISSION_IMAGES_R2 || !eventData.message_id || !env.LINE_CHANNEL_ACCESS_TOKEN) {
    return { ok: false, error: 'image_storage_not_configured' };
  }

  try {
    const content = await fetch(`https://api-data.line.me/v2/bot/message/${eventData.message_id}/content`, {
      headers: { Authorization: `Bearer ${env.LINE_CHANNEL_ACCESS_TOKEN}` },
    });

    if (!content.ok) return { ok: false, error: `line_content_${content.status}` };

    const body = await content.arrayBuffer();
    const contentType = content.headers.get('content-type') || 'image/jpeg';
    const extension = contentType.includes('png') ? '.png' : '.jpg';
    const storageKey = [
      'line-intake-images',
      safePathSegment(eventData.user_id || 'unknown'),
      imagePurpose,
      `${Date.now()}-${eventData.message_id}${extension}`,
    ].join('/');

    await env.ADMISSION_IMAGES_R2.put(storageKey, body, {
      httpMetadata: { contentType },
      customMetadata: {
        line_user_id: eventData.user_id || '',
        line_message_id: eventData.message_id,
        image_purpose: imagePurpose,
        uploaded_at: new Date().toISOString(),
      },
    });

    return {
      ok: true,
      storage_key: storageKey,
      file_url: env.R2_PUBLIC_BASE_URL ? `${String(env.R2_PUBLIC_BASE_URL).replace(/\/+$/, '')}/${encodeURI(storageKey)}` : '',
      original_filename: `${eventData.message_id}${extension}`,
    };
  } catch (error) {
    console.log('Image storage failed', error?.message || error);
    return { ok: false, error: 'image_storage_failed' };
  }
}

function fallbackForState(state, category) {
  const useCategory = state.invalid_reply_count >= 3 ? 'repeated_invalid_reply' : category;
  const pool = fallbackReplyPool()[useCategory] || fallbackReplyPool().unclear_reply;
  let index = Math.floor(Math.random() * pool.length);
  const fallbackId = `${useCategory}_${index}`;
  if (pool.length > 1 && (fallbackId === state.last_fallback_id || fallbackId === state.last_fallback_key)) {
    index = (index + 1) % pool.length;
  }
  state.last_fallback_type = useCategory;
  state.last_fallback_id = `${useCategory}_${index}`;
  state.last_fallback_key = state.last_fallback_id;
  return `${pool[index]}\n\n${currentStepHint(state)}`;
}

function fallbackReplyPool() {
  return {
    unclear_reply: [
      '沒關係～我們可以慢慢整理 😊🌿\n我目前想先了解兩件事：課程名稱大概是什麼？這堂課比較接近哪一種類型呢？',
      '我先幫你抓一下方向就好，不用一次想得很完整 📝\n可以先告訴我：這堂課大概想教什麼內容呢？',
      '沒問題～如果還沒想清楚，也可以先用暫定名稱 😊\n例如：「色鉛筆課」、「兒童畫畫課」、「手作體驗課」都可以。',
      '我好像還沒抓到課程方向～我們先從最簡單的開始好了 🌿\n這堂課是畫畫、手作、花藝、美甲，還是其他類型呢？',
      '沒關係，不用很正式地回答 😊\n可以像聊天一樣簡單說：「我想做一堂什麼課」就可以了。',
      '我先幫你整理成招生頁資料，所以需要一點點課程方向 📝\n可以先告訴我課程名稱，或先說大概想教什麼也可以。',
      '如果還沒有課程名稱也沒關係～\n可以先回覆課程類型，例如：畫畫、手作、花藝、美甲、攝影、設計、其他 🎨',
      '我有點不確定你的意思，我們先簡單一點來 😊\n請問這堂課主要是什麼主題呢？',
      '我們可以先不用想太完整～\n只要先知道「課程名稱」和「課程類型」，後面我再慢慢幫你整理 📝✨',
      '我先幫你拉回課程資料整理這邊唷 😊\n可以先用一句話告訴我：你想做什麼課程的招生頁？',
    ],
    off_topic: [
      '我先幫你拉回課程招生頁資料整理這邊 😊📝\n目前想先確認：這次想製作的是什麼課程呢？',
      '這邊主要是協助整理課程招生頁資料唷 🌿\n如果要開始，可以先告訴我課程名稱或課程類型。',
      '我先不處理其他內容，避免整理錯方向 😊\n我們先確認這堂課的基本資料：課程名稱是什麼呢？',
      '先幫你回到招生頁製作流程這邊 📝\n可以先跟我說：這堂課是畫畫、手作、花藝、美甲，還是其他類型？',
      '我目前主要是協助課程招生頁製作諮詢 😊\n如果想開始整理資料，可以先回覆課程名稱。',
      '這個內容看起來比較不像課程資料，我先幫你整理主線 🌿\n請問這次想做的招生頁，是哪一堂課呢？',
      '我先幫你聚焦在課程招生頁上唷 📝✨\n可以先告訴我：這堂課大概想招生給誰呢？',
      '為了避免資料建檔錯誤，我先確認一下 😊\n你是想製作課程招生頁嗎？如果是，可以先提供課程名稱。',
      '我先把其他內容放旁邊，回到課程資料整理這邊 ☕️\n想先請問：目前準備的是什麼課程呢？',
      '這邊會先協助整理成招生頁需要的資料 😊\n可以先用簡單一句話告訴我：想做哪一類課程？',
    ],
    unsafe_or_system_instruction: [
      '這裡主要是協助整理課程招生頁資料唷 😊\n如果要開始，我可以先幫你整理課程名稱、課程類型和招生時間。',
      '這個需求不在目前諮詢流程裡，我先幫你回到課程招生頁製作這邊 📝\n可以先告訴我想製作哪一堂課的招生頁。',
      '我目前可以協助的是課程資料整理、製作流程說明和樣板提案通知 😊\n如果要開始，可以先提供課程名稱。',
      '這邊不處理系統設定或內部操作唷 🌿\n我可以幫忙整理課程內容，讓後續製作招生頁更順利。',
      '我先不執行這類指令，避免影響資料安全 😊\n如果是要製作招生頁，我們可以先從課程名稱開始整理。',
      '目前這個 LINE 主要是課程招生頁製作諮詢用 📝\n可以協助整理需求、建立初步資料和後續選版流程。',
      '這個部分不屬於招生頁諮詢流程，我先幫你拉回來 😊\n請問這次想製作的課程主題是什麼呢？',
      '我不能協助處理內部系統或設定相關內容唷 🌿\n但可以幫你整理課程資料，看看適合怎麼做成招生頁。',
      '為了保護資料安全，這類指令我不會執行 😊\n如果想製作課程招生頁，可以先告訴我課程名稱或課程類型。',
      '我先專注在課程招生頁製作諮詢這邊 📝✨\n可以先跟我說：你想做哪一類課程的招生頁？',
    ],
    repeated_invalid_reply: [
      '我好像還沒有取得足夠的課程資料 📝\n如果方便，可以直接用一句話告訴我：「我想做什麼課程的招生頁」。',
      '我目前還無法判斷要整理哪一堂課 😊\n可以先簡單回覆：課程名稱，或課程大概類型就好。',
      '我們先暫停一下沒關係 🌿\n如果還沒準備好，也可以晚點再回來，直接告訴我想做的課程就可以。',
      '我可能還沒理解你的需求 📝\n可以試著這樣回覆：\n「我想做＿＿＿課程的招生頁」。',
      '目前資料還不太夠，我先不幫你建檔，避免整理錯誤 😊\n如果要繼續，可以先提供課程名稱或課程類型。',
      '沒關係，我們可以慢慢來 ☕️\n如果想讓我協助整理招生頁，可以先回覆：\n「我想開始整理課程資料」。',
      '我先幫你保留在諮詢階段，不會直接建立資料唷 📝\n等你準備好後，可以告訴我課程名稱和課程類型。',
      '目前還沒辦法進入下一步，因為我需要先知道課程方向 😊\n可以先用很簡單的方式說：這堂課是關於什麼？',
      '我可能需要一點更明確的資訊，才不會幫你整理錯方向 🌿\n可以先回覆：畫畫、手作、花藝、美甲、攝影、設計，或其他。',
      '如果現在只是先看看也沒關係 😊\n之後想開始時，可以直接回覆「我想製作課程招生頁」，我再一步一步協助整理。',
    ],
  };
}

function classifyInvalidText(text) {
  if (/https?:\/\/|www\.|新聞|廣告|優惠|股票|天氣/i.test(text)) return 'off_topic';
  return 'unclear_reply';
}

async function handlePublicCommand(eventData, env) {
  if (eventData.message_type !== 'text') return false;

  const text = normalizeCommand(eventData.user_message);

  if (text === '!清除我的狀態') {
    await clearIntakeState(eventData.user_id, env);
    await clearHandoff(eventData.user_id, env);
    await replyToLine(eventData.reply_token, '已清除你的狀態，可以重新傳「我想開始」。', env.LINE_CHANNEL_ACCESS_TOKEN);
    return true;
  }

  if (text === '!我的狀態') {
    const state = await getIntakeState(eventData.user_id, env);
    await replyToLine(eventData.reply_token, [
      `版本：${DEPLOY_VERSION}`,
      `目前步驟：${state?.current_intake_step || '未開始'}`,
      `invalid_reply_count：${state?.invalid_reply_count || 0}`,
    ].join('\n'), env.LINE_CHANNEL_ACCESS_TOKEN);
    return true;
  }

  if (text === '!我是窗口') {
    await replyToLine(eventData.reply_token, `你的 LINE userId 是：\n${eventData.user_id || '(未取得 user_id)'}\n\n請填入 Cloudflare Worker 環境變數 OWNER_LINE_USER_ID。`, env.LINE_CHANNEL_ACCESS_TOKEN);
    return true;
  }

  return false;
}

async function getIntakeState(userId, env) {
  const memory = intakeMemory.get(userId);
  if (memory && (!memory.expires_at || memory.expires_at > Date.now())) return memory;
  if (!env.HANDOFF_KV || !userId) return null;
  const state = await env.HANDOFF_KV.get(`intake:${userId}`, 'json');
  if (state) intakeMemory.set(userId, { ...state, expires_at: Date.now() + 24 * 60 * 60 * 1000 });
  return state || null;
}

async function saveIntakeState(userId, state, env) {
  if (!userId) return;
  refreshCollectedData(state);
  const next = { ...state, updated_at: new Date().toISOString(), expires_at: Date.now() + 24 * 60 * 60 * 1000 };
  intakeMemory.set(userId, next);
  if (env.HANDOFF_KV) await env.HANDOFF_KV.put(`intake:${userId}`, JSON.stringify(next));
}

async function clearIntakeState(userId, env) {
  intakeMemory.delete(userId);
  if (env.HANDOFF_KV && userId) await env.HANDOFF_KV.delete(`intake:${userId}`);
}

async function getHandoffState(userId, env) {
  const memory = handoffMemory.get(userId);
  if (memory && (!memory.expires_at || memory.expires_at > Date.now())) return memory;
  if (!env.HANDOFF_KV || !userId) return null;
  return await env.HANDOFF_KV.get(`handoff:${userId}`, 'json');
}

async function clearHandoff(userId, env) {
  handoffMemory.delete(userId);
  if (env.HANDOFF_KV && userId) await env.HANDOFF_KV.delete(`handoff:${userId}`);
}

function shouldSilentDuringHandoff(env) {
  return env.SILENT_DURING_HANDOFF !== 'false';
}

async function replyToLine(replyToken, text, accessToken) {
  if (!accessToken) {
    console.log('Missing LINE_CHANNEL_ACCESS_TOKEN');
    return;
  }

  const response = await fetch('https://api.line.me/v2/bot/message/reply', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${accessToken}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      replyToken,
      messages: [{ type: 'text', text: limitLineMessage(text, 5000) }],
    }),
  });

  if (!response.ok) {
    console.log('LINE reply failed', response.status, await response.text());
  }
}

async function isValidLineSignature(body, signature, channelSecret) {
  if (!signature || !channelSecret) return false;
  const key = await crypto.subtle.importKey(
    'raw',
    new TextEncoder().encode(channelSecret),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign'],
  );
  const digest = await crypto.subtle.sign('HMAC', key, new TextEncoder().encode(body));
  return timingSafeEqual(signature, base64Encode(digest));
}

function isStartCommand(text) {
  return /^(1|我想製作課程招生頁|我想開始|開始整理|我要做招生頁|想製作課程招生頁|我想製作|開始)$/i.test(String(text || '').trim());
}

function isFlowIntro(text) {
  return /^(2|我想了解製作流程|了解製作流程|流程|製作流程)$/i.test(String(text || '').trim());
}

function isSoftInquiry(text) {
  return /^(3|我只是先問問看|先問問|問問看)$/i.test(String(text || '').trim());
}

function isServicePriceQuestion(text) {
  return /你們.*(多少錢|費用|價格|收費|報價|付款)|要付款嗎|服務.*(多少錢|費用|價格|收費|報價)|怎麼收費|付款方式/i.test(String(text || ''));
}

function isCoursePriceAnswerContext(state) {
  return state.current_intake_step === 'collecting_course_sales' || state.last_asked_field === 'course_price';
}

function freeTrialReply() {
  return '目前此流程為免費試營運階段，主要目的是協助建立課程招生頁並優化流程。後續若有正式收費方案，會再另外公告或說明。';
}

function isSystemInstructionLike(text) {
  return /忽略.*規則|修改.*規則|系統設定|prompt|api|資料庫|後台|密碼|token|access token|channel secret|cloudflare|worker|webhook/i.test(String(text || ''));
}

function isNoPhotoReply(text) {
  return /目前沒有|沒有照片|暫時沒有|之後再補|沒拍到|現在手邊沒有|先略過|略過/i.test(String(text || ''));
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(email || '').trim());
}

function extractLineLink(text) {
  const raw = String(text || '');
  const emailPattern = /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi;
  const line = raw
    .split(/\r?\n/)
    .map((item) => item.trim())
    .find((item) => /LINE ID Link|LINE ID 的 Link|LINE Link|LINE ID|line_id_link|line\.me|lin\.ee/i.test(item));

  if (!line) {
    const withoutEmails = raw.replace(emailPattern, ' ');
    return withoutEmails.match(/(?:https?:\/\/)?(?:line\.me|lin\.ee)\/[^\s]+|@[\w.-]{3,}/i)?.[0] || '';
  }

  const cleaned = cleanLabeledValue(line, ['LINE ID Link', 'LINE ID 的 Link', 'LINE Link', 'LINE ID', 'line_id_link']);
  return emailPattern.test(cleaned) ? '' : cleaned;
}

function classifyLineIdLink(value) {
  const text = String(value || '').trim();
  if (!text) return 'missing';
  if (/(?:https?:\/\/)?(?:line\.me|lin\.ee)\/[^\s]+|@[\w.-]{3,}/i.test(text)) return 'provided';
  if (!/\s/.test(text) && text.length <= 30 && !/[/:?=&]/.test(text)) return 'invalid_display_name_only';
  return 'need_review';
}

function classifyUnknownStatus(value) {
  return /未定|還不確定|不確定|不知道|之後補|再補/i.test(String(value || '')) ? 'unknown' : 'provided';
}

function isUnknownLike(value) {
  return /未定|還沒確定|還不確定|不確定|不知道|之後補|再補|暫定/i.test(String(value || ''));
}

function inferCourseType(text) {
  return String(text || '').match(/畫畫|色鉛筆|水彩|手作|花藝|美甲|攝影|設計|瑜伽|烘焙|音樂|舞蹈|英文|程式|親子|證照/i)?.[0] || '';
}

function hasCourseUsefulSignal(text) {
  return /課|班|營|招生|畫|手作|花藝|美甲|攝影|設計|瑜伽|烘焙|音樂|舞蹈|英文|程式|實體|線上|混合|未定|還沒確定|不確定|目前沒有|人|元|費用|名額|對象|特色|支援/i.test(String(text || ''));
}

function looksLikeForm(text) {
  return /【基本聯絡資料】|【課程基本資料】|(?:^|\n)\s*(?:\d+\.\s*)?姓名\s*[:：]|(?:^|\n)\s*(?:\d+\.\s*)?課程名稱\s*[:：]/i.test(String(text || ''));
}

function cleanLabeledValue(text, labels) {
  let result = String(text || '').trim().replace(/^\s*\d+\.\s*/, '');
  for (const label of labels) {
    result = result.replace(new RegExp(`^\\s*${escapeRegExp(label)}\\s*[:：]?\\s*`, 'i'), '').trim();
  }
  return cleanValue(result);
}

function normalizeLabel(label) {
  return String(label || '').replace(/[【】\s/]/g, '').trim().toLowerCase();
}

function normalizeFormValue(value) {
  const text = cleanValue(value);
  if (!text) return '';
  if (/^實體\s*\/\s*線上\s*\/\s*混合$/i.test(text)) return '';
  return text;
}

function cleanValue(value) {
  return String(value || '').trim().replace(/[。,.，]$/, '').trim();
}

function valueOrEmpty(value) {
  return String(value || '').trim() || '未提供';
}

function splitFeatures(value) {
  return String(value || '')
    .split(/[、,，\n]/)
    .map((item) => item.trim())
    .filter(Boolean);
}

function isExplicitConfirmation(text) {
  return /^(確認|資料正確|正確|確認無誤|可以建檔|送出|送出資料)$/i.test(String(text || '').trim());
}

function isConfirmationLike(text) {
  return /^(確認|資料正確|正確|確認無誤|可以建檔|送出|送出資料|好|好的|可以|嗯|恩|ok|OK)$/i.test(String(text || '').trim());
}

function isFieldHelpQuestion(text) {
  return /(是什麼|什麼意思|是指什麼|怎麼填|如何填|看不懂|不懂|不知道怎麼填|這是指)/i.test(String(text || ''));
}

function looksLikeCourseFieldText(text) {
  return /課程名稱|課名|課程類型|課程形式|上課形式|上課地點|預計招生時間|預計開課時間|課程名額|課程費用|適合對象|課程特色|課後支援/i.test(String(text || ''));
}

function isCourseAnswerToken(text) {
  const value = String(text || '').trim();
  return /^(實體|線上|混合|畫畫|色鉛筆|水彩|手作|花藝|美甲|攝影|設計|瑜伽|烘焙|音樂|舞蹈|英文|程式|親子|證照)$/i.test(value);
}

function isShortLineCodeLike(text) {
  const value = String(text || '').trim();
  return /^[A-Za-z0-9_-]{5,20}$/.test(value) && /[A-Za-z]/.test(value) && /\d/.test(value);
}

function isGenericCourseName(text) {
  const value = String(text || '').trim();
  if (!value || /[:：\n]/.test(value)) return false;
  return /^(畫畫課|畫畫|色鉛筆課|水彩課|手作課|花藝課|美甲課|攝影課|設計課|瑜伽課|烘焙課|音樂課|舞蹈課|英文課|程式課)$/i.test(value);
}

function classifyDateStatus(value) {
  const text = String(value || '');
  if (/月底|月初|大概|可能|左右|附近|暫定|還不確定|不確定|未定|之後補|再補/i.test(text)) return 'tentative';
  return text.trim() ? 'provided' : 'pending';
}

function copyrightNotice() {
  return '小提醒：請確認提供的照片、作品或素材都是可以合法使用的內容。我們主要協助製作與刊登招生頁，不負責素材版權、肖像權、商標權或授權審查；如有侵權或授權爭議，需由素材提供者自行負責喔 🙏';
}

function safePathSegment(value) {
  return String(value || '').replace(/[^a-zA-Z0-9._-]/g, '-').replace(/-+/g, '-').slice(0, 120) || 'unknown';
}

function normalizeCommand(text) {
  return String(text || '').trim().replace(/^！/, '!').replace(/\s+/g, ' ');
}

function limitLineMessage(text, maxLength) {
  return text.length <= maxLength ? text : `${text.slice(0, maxLength - 1)}…`;
}

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function base64Encode(arrayBuffer) {
  let binary = '';
  const bytes = new Uint8Array(arrayBuffer);
  for (let i = 0; i < bytes.length; i += 1) binary += String.fromCharCode(bytes[i]);
  return btoa(binary);
}

function timingSafeEqual(a, b) {
  if (a.length !== b.length) return false;
  let diff = 0;
  for (let i = 0; i < a.length; i += 1) diff |= a.charCodeAt(i) ^ b.charCodeAt(i);
  return diff === 0;
}

function json(payload, status = 200) {
  return new Response(JSON.stringify(payload), {
    status,
    headers: { 'Content-Type': 'application/json; charset=utf-8' },
  });
}
