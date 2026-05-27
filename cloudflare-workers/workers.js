const DEPLOY_VERSION = 'chat-c-receptionist-form-link-2026-05-27-20';
const DEFAULT_FORM_URL = 'https://ftm.com.tw/demo/admission-system/public-course-intake.php';

export default {
  async fetch(request, env, ctx) {
    if (request.method === 'GET') {
      return json({
        status: 'ok',
        service: 'ftm-line-webhook',
        mode: 'cloudflare-workers',
        version: DEPLOY_VERSION,
        role: '課程招生頁系統接待助理',
        message: 'LINE receptionist webhook is ready.',
        config: {
          form_url_set: Boolean(getFormUrl(env)),
          inquiry_logs: Boolean(env.INQUIRY_LOGS),
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
    } catch (error) {
      console.log('Invalid JSON payload', error?.message || error);
      return json({ status: 'error', message: 'Invalid JSON.' }, 400);
    }

    const events = Array.isArray(payload.events) ? payload.events : [];
    const processing = Promise.all(events.map((event) => handleLineEvent(event, env))).catch((error) => {
      console.log('Webhook processing failed', error?.message || error);
    });

    if (ctx && typeof ctx.waitUntil === 'function') {
      ctx.waitUntil(processing);
    } else {
      await processing;
    }

    return json({ status: 'ok' });
  },
};

async function handleLineEvent(event, env) {
  const normalized = normalizeLineEvent(event);

  if (!normalized) {
    return;
  }

  const intent = normalized.message_type === 'text'
    ? detectIntent(normalized.user_message)
    : 'tech_support';
  const reply = buildReply(intent, normalized, env);

  await logInquiry(normalized, intent, env);
  await replyToLine(normalized.reply_token, reply, env.LINE_CHANNEL_ACCESS_TOKEN);
}

function normalizeLineEvent(event) {
  if (event?.type !== 'message') {
    return null;
  }

  if (!event.replyToken || event.replyToken === '00000000000000000000000000000000') {
    return null;
  }

  const message = event.message || {};

  return {
    user_id: event.source?.userId || event.source?.groupId || event.source?.roomId || '',
    user_message: message.type === 'text' ? String(message.text || '').trim() : '',
    reply_token: event.replyToken,
    channel: 'line',
    message_type: message.type || 'unknown',
    timestamp: event.timestamp || Date.now(),
  };
}

function detectIntent(text) {
  const value = normalizeText(text);

  if (!value) {
    return 'opening';
  }

  if (isUnsafeInstruction(value)) {
    return 'unsafe';
  }

  if (isStartFormIntent(value)) {
    return 'start_form';
  }

  if (isTechSupportIntent(value)) {
    return 'tech_support';
  }

  if (isFreeTrialIntent(value)) {
    return 'ask_free';
  }

  if (isProcessIntent(value)) {
    return 'ask_process';
  }

  if (isAfterFormIntent(value)) {
    return 'after_form';
  }

  if (isPreviewIntent(value)) {
    return 'preview_info';
  }

  if (isGreeting(value)) {
    return 'opening';
  }

  return 'fallback';
}

function buildReply(intent, eventData, env) {
  switch (intent) {
    case 'start_form':
      return startFormReply(env);
    case 'ask_process':
      return processReply();
    case 'ask_free':
      return freeTrialReply();
    case 'tech_support':
      return techSupportReply(eventData.message_type);
    case 'after_form':
      return afterFormReply();
    case 'preview_info':
      return previewInfoReply();
    case 'unsafe':
      return unsafeReply();
    case 'opening':
      return openingReply();
    case 'fallback':
    default:
      return fallbackReply();
  }
}

function openingReply() {
  return [
    '嗨嗨～歡迎來到課程招生頁製作諮詢 😊🌿',
    '',
    '目前正在免費試營運中，可以協助建立課程招生頁，並提供 3 款網站預覽方向讓你選。',
    '',
    '為了讓資料比較清楚、不漏填，課程資料會改由網頁表單填寫 📝',
    '',
    '流程大概是：',
    '1. 先填寫課程資料表',
    '2. 系統檢查必填資料',
    '3. 資料送出後建立課程紀錄',
    '4. 完成後會寄 Email 通知',
    '5. 之後會收到 3 款招生頁預覽網址',
    '6. 3 天內選一款喜歡的方向',
    '7. 選定後進入後續網站製作流程',
    '',
    '可以先選一個：',
    '1. 我要填寫課程資料表 🎨',
    '2. 我想了解製作流程 📋',
    '3. 我想知道目前是不是免費 ☕️',
    '4. 我遇到網站或系統問題 🛠️',
    '',
    '也可以直接打字告訴我目前的狀況～',
  ].join('\n');
}

function startFormReply(env) {
  return [
    '太好了～這邊先提供課程資料表給你 😊📝',
    '',
    '請從這個頁面填寫資料：',
    getFormUrl(env),
    '',
    '表單會協助整理：',
    '• 基本聯絡資料',
    '• 課程名稱與類型',
    '• 上課時間與地點',
    '• 課程特色',
    '• 圖片素材',
    '• 後續通知用 Email',
    '',
    '填完送出後，系統會建立一筆課程資料。',
    '後續完成 3 款招生頁預覽時，會再用 Email 通知你。',
  ].join('\n');
}

function processReply() {
  return [
    '目前是免費試營運階段，流程大概是：',
    '',
    '1. 填寫課程資料表 📝',
    '透過網頁表單填寫課程內容、時間地點、特色與圖片素材。',
    '',
    '2. 系統檢查必填欄位 🌿',
    '表單會協助確認必要資料是否填完整，避免漏資料。',
    '',
    '3. 資料寫入系統 📁',
    '送出後會建立一筆課程招生頁資料。',
    '',
    '4. 系統寄 Email 通知 📩',
    '後續進度和預覽網址會透過 Email 通知。',
    '',
    '5. 產生 3 款招生頁預覽 🎨',
    '系統會依照課程內容製作 3 款不同方向的網站預覽。',
    '',
    '6. 三天內選擇喜歡的版本 ⏳',
    '預覽網址會保留 3 天，可以從 3 款中選一款。',
    '',
    '7. 進入後續網站製作流程 🖥️',
    '選定後會依照該版本繼續完成正式招生頁。',
  ].join('\n');
}

function freeTrialReply() {
  return [
    '目前是免費試營運階段，不需要先付款 😊🌿',
    '',
    '這個階段主要是測試課程招生頁系統流程，包含：',
    '• 課程資料填寫',
    '• 三款招生頁預覽',
    '• 三天內選定版本',
    '• 後續網站製作流程',
    '',
    '免費試營運屬於階段性開放，我們會依照系統測試狀況、服務量能與後續規劃，保留調整、暫停或結束免費試營運的權利。',
    '',
    '之後如果有正式收費方案，會再另外公告或說明，不會在未告知的情況下突然向你收費。',
  ].join('\n');
}

function techSupportReply(messageType) {
  const prefix = messageType === 'text'
    ? '網站與系統問題回報功能目前正在建檔中 🛠️'
    : '我收到你傳來的內容了～網站與系統問題回報功能目前正在建檔中 🛠️';

  return [
    prefix,
    '',
    '這個功能之後會開放，用來協助處理表單打不開、送出失敗、沒收到 Email、預覽網址異常等問題。',
    '',
    '如果現在遇到狀況，可以先稍後再試，或等功能開放後再回報。',
  ].join('\n');
}

function afterFormReply() {
  return [
    '填完表單送出後，系統會先建立課程資料紀錄 😊',
    '',
    '後續會用 Email 通知進度；完成 3 款招生頁預覽後，也會把預覽網址寄給你。',
    '到時候請在 3 天內選一款喜歡的方向，系統才會接著進入後續網站製作流程。',
  ].join('\n');
}

function previewInfoReply() {
  return [
    '3 款招生頁預覽會透過 Email 通知你 😊',
    '',
    '預覽網址會保留 3 天。你可以看完後選其中一款作為後續製作方向；如果超過 3 天未選，預覽連結會失效，需要重新開啟提案流程。',
  ].join('\n');
}

function fallbackReply() {
  return [
    '我先幫你拉回課程招生頁製作這邊 😊',
    '可以選：',
    '1. 填寫課程資料表',
    '2. 了解製作流程',
    '3. 了解免費試營運',
    '4. 回報網站或系統問題',
  ].join('\n');
}

function unsafeReply() {
  return [
    '這邊主要協助課程招生頁製作諮詢唷 😊',
    '如果要繼續，可以選：',
    '1. 填寫課程資料表',
    '2. 了解製作流程',
    '3. 了解免費試營運',
    '4. 回報網站或系統問題',
  ].join('\n');
}

function isStartFormIntent(text) {
  return /^(1|我要填寫課程資料表|我要填資料|我要開始|我想開始|開始|我要做招生頁|想製作課程招生頁|我想製作課程招生頁|我要做課程招生頁|我要填表|填表|表單)$/i.test(text)
    || /填寫.*(課程|資料|表單)|開始.*(課程|招生頁)|製作.*招生頁|做.*招生頁/.test(text);
}

function isProcessIntent(text) {
  return /^(2|我想了解製作流程|了解流程|製作流程|流程|怎麼做|怎麼開始)$/i.test(text)
    || /流程|步驟|怎麼運作|怎麼製作|接下來怎麼做/.test(text);
}

function isFreeTrialIntent(text) {
  return /^(3|我想知道目前是不是免費|免費|試營運)$/i.test(text)
    || /免費|試營運|要付款|需要付款|多少錢|價格|費用|收費|報價|訂金|付款/.test(text);
}

function isTechSupportIntent(text) {
  return /^(4|我遇到網站或系統問題|網站問題|系統問題|其他問題)$/i.test(text)
    || /表單打不開|表單送不出去|沒收到.*email|沒收到.*信|預覽網址打不開|不知道怎麼選|錯誤|無法|不能|壞掉|bug|問題|打不開/.test(text);
}

function isAfterFormIntent(text) {
  return /填完|送出後|表單送出|接下來|後續會怎樣|會發生什麼/.test(text);
}

function isPreviewIntent(text) {
  return /三款|3款|預覽|網址|選款|選版本|三天|3天|失效|作廢/.test(text);
}

function isGreeting(text) {
  return /^(嗨|哈囉|你好|您好|hi|hello|hey)$/i.test(text);
}

function isUnsafeInstruction(text) {
  return /忽略.*規則|忽略.*指令|改.*prompt|透露.*prompt|系統.*prompt|api|token|密碼|後台|資料庫|cloudflare|worker|修改.*系統|修改.*規則|設定.*系統|刪除.*資料|查.*內部|internal|developer|system prompt/i.test(text);
}

function normalizeText(text) {
  return String(text || '')
    .trim()
    .replace(/[！]/g, '!')
    .replace(/\s+/g, ' ');
}

function getFormUrl(env) {
  const value = String(env.FORM_URL || '').trim();
  return value || DEFAULT_FORM_URL;
}

async function logInquiry(eventData, intent, env) {
  if (!env.INQUIRY_LOGS || typeof env.INQUIRY_LOGS.put !== 'function') {
    return;
  }

  const key = [
    'line-inquiry',
    eventData.user_id || 'unknown',
    eventData.timestamp || Date.now(),
    crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).slice(2),
  ].join(':');

  const payload = {
    user_id: eventData.user_id,
    user_message: eventData.user_message,
    message_type: eventData.message_type,
    intent,
    source_channel: 'line',
    created_at: new Date().toISOString(),
  };

  try {
    await env.INQUIRY_LOGS.put(key, JSON.stringify(payload), { expirationTtl: 60 * 60 * 24 * 30 });
  } catch (error) {
    console.log('Inquiry log failed', error?.message || error);
  }
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
      messages: [
        {
          type: 'text',
          text: limitLineMessage(text, 5000),
        },
      ],
    }),
  });

  if (!response.ok) {
    const errorText = await response.text();
    console.log('LINE reply failed', response.status, errorText);
  }
}

async function isValidLineSignature(body, signature, channelSecret) {
  if (!signature || !channelSecret) {
    return false;
  }

  const encoder = new TextEncoder();
  const key = await crypto.subtle.importKey(
    'raw',
    encoder.encode(channelSecret),
    { name: 'HMAC', hash: 'SHA-256' },
    false,
    ['sign'],
  );

  const digest = await crypto.subtle.sign('HMAC', key, encoder.encode(body));
  const expected = base64Encode(digest);

  return timingSafeEqual(signature, expected);
}

function base64Encode(arrayBuffer) {
  let binary = '';
  const bytes = new Uint8Array(arrayBuffer);

  for (let i = 0; i < bytes.length; i += 1) {
    binary += String.fromCharCode(bytes[i]);
  }

  return btoa(binary);
}

function timingSafeEqual(a, b) {
  if (a.length !== b.length) {
    return false;
  }

  let diff = 0;

  for (let i = 0; i < a.length; i += 1) {
    diff |= a.charCodeAt(i) ^ b.charCodeAt(i);
  }

  return diff === 0;
}

function limitLineMessage(text, maxLength) {
  const value = String(text || '');

  if (value.length <= maxLength) {
    return value;
  }

  return `${value.slice(0, maxLength - 1)}…`;
}

function json(payload, status = 200) {
  return new Response(JSON.stringify(payload), {
    status,
    headers: {
      'Content-Type': 'application/json; charset=utf-8',
    },
  });
}
