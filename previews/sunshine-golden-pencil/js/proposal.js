const data = window.SUNSHINE_PREVIEW_DATA;
const params = window.getPreviewParams();
const proposal = data.proposals.find((item) => item.proposal_id === params.proposalId) || data.proposals[0];
const state = window.resolvePreviewState(proposal, params);
const course = data.course_project;
const assets = data.assets;
const root = document.querySelector("#previewRoot");

document.title = `${proposal.proposal_id} 款｜${proposal.proposal_name}｜招生頁預覽`;
root.className = `preview-page preview-page--${proposal.variant} is-${state.previewStatus} is-${state.selectionStatus}`;

const statusText = state.isExpired
  ? "此預覽已逾期"
  : state.isSelected
    ? "此款已被選定"
    : state.isNotSelected
      ? "此款未被選定"
      : "客戶預覽中";

function metadata() {
  return `
    <aside class="preview-meta" aria-label="預覽資料">
      <span>${statusText}</span>
      <span>${proposal.proposal_code}</span>
      <span>期限：${state.expiresAt}</span>
    </aside>
  `;
}

function gateMessage() {
  if (!state.isExpired && !state.isNotSelected) return "";
  const title = state.isExpired ? "預覽期限已過" : "此提案未被選定";
  const body = state.isExpired
    ? "這個 preview_url 已超過 expires_at，前台不再顯示完整預覽內容。請由後台重新開啟或產生新的預覽連結。"
    : "客戶已選擇其他 proposal，此版本不可繼續作為正式製作來源，除非後台重新開啟。";
  return `
    <section class="preview-gate" role="status">
      <h1>${title}</h1>
      <p>${body}</p>
      <dl>
        <div><dt>proposal_id</dt><dd>${proposal.proposal_id}</dd></div>
        <div><dt>proposal_code</dt><dd>${proposal.proposal_code}</dd></div>
        <div><dt>preview_status</dt><dd>${state.previewStatus}</dd></div>
        <div><dt>selection_status</dt><dd>${state.selectionStatus}</dd></div>
      </dl>
    </section>
  `;
}

function formBlock(buttonText = "送出報名") {
  return `
    <form class="preview-form" data-preview-form>
      <label>姓名<input name="name" required></label>
      <label>手機<input name="phone" required></label>
      <label>Email<input name="email" type="email"></label>
      <label>LINE ID<input name="line_id"></label>
      <label>方便聯絡時間<input name="preferred_time"></label>
      <label>匯款帳號後五碼<input name="payment_last5" maxlength="20"></label>
      <label class="is-wide">備註 / 想詢問的問題<textarea name="message"></textarea></label>
      <button type="submit">${buttonText}</button>
    </form>
  `;
}

function renderAir() {
  return `
    ${metadata()}
    ${gateMessage()}
    <div class="proposal-shell">
      <header class="air-nav"><a href="./index.html">三款預覽</a><a href="#signup">查看報名</a></header>
      <section class="air-hero">
        <figure><img src="${assets.hero_work}" alt="小黃金色鉛筆作品"></figure>
        <div>
          <p>${proposal.proposal_name}｜${proposal.primary_template_id} + ${proposal.secondary_template_id}</p>
          <h1>${course.course_title}</h1>
          <p>${course.course_subtitle}</p>
        </div>
        <dl>
          <div><dt>地點</dt><dd>${course.location}</dd></div>
          <div><dt>時間</dt><dd>${course.time}</dd></div>
          <div><dt>費用</dt><dd>${course.price}</dd></div>
        </dl>
      </section>
      <section class="air-about">
        <div><p>關於課程</p><h2>以清爽留白承接作品質感，讓課程像畫室型錄而不是銷售頁。</h2></div>
        <p>本款依 Chat A proposal 的 TPL-005 清爽空間感作為主骨架，並以 TPL-007 的作品展示秩序補強 gallery。所有可見標題以中文呈現，桌機字級維持克制。</p>
      </section>
      <section class="air-gallery" aria-label="作品展示">
        <figure class="lead"><img src="${assets.work_collage}" alt="寵物色鉛筆作品集合"><figcaption>作品集合</figcaption></figure>
        <figure><img src="${assets.process_02}" alt="學員描繪寵物毛流"><figcaption>課堂過程</figcaption></figure>
        <figure><img src="${assets.work_gray}" alt="灰白貓色鉛筆作品"><figcaption>毛色練習</figcaption></figure>
      </section>
      <section class="air-cta" id="signup">
        <div><p>報名預覽</p><h2>${course.payment_note}</h2></div>
        ${formBlock("預覽送出")}
      </section>
    </div>
  `;
}

function renderBrand() {
  return `
    ${metadata()}
    ${gateMessage()}
    <div class="proposal-shell">
      <section class="brand-hero">
        <img src="${assets.teacher}" alt="鄭凱玲老師與大型寵物作品">
        <div>
          <p>${proposal.proposal_name}｜${proposal.primary_template_id} + ${proposal.secondary_template_id}</p>
          <h1>把牠的神情，畫成一場安靜的展覽。</h1>
          <a href="#signup">預約席位</a>
        </div>
      </section>
      <section class="brand-about">
        <p>關於課程</p>
        <h2>${course.course_title}</h2>
        <p>本款以 TPL-002 的高級形象 Hero 建立第一眼氣勢，再用 TPL-004 的大圖與專業節奏加強課程可信度。</p>
      </section>
      <section class="brand-modules" aria-label="課程導覽">
        <article><span>一</span><h3>觀察輪廓</h3><p>從照片整理姿態與比例。</p></article>
        <article><span>二</span><h3>毛流層次</h3><p>疊出暖色光線與柔軟毛感。</p></article>
        <article><span>三</span><h3>眼神表情</h3><p>處理高光、鼻口與表情細節。</p></article>
      </section>
      <section class="brand-gallery" aria-label="作品展示">
        <figure><img src="${assets.hero_work}" alt="小黃金色鉛筆作品"></figure>
        <figure><img src="${assets.process_03}" alt="寵物色鉛筆細節練習"></figure>
        <figure><img src="${assets.work_costume}" alt="造型貓色鉛筆作品"></figure>
      </section>
      <section class="brand-cta" id="signup">
        <div><p>席位資訊</p><h2>${course.time}｜${course.price}</h2><p>${course.seat_note}</p></div>
        ${formBlock("預約席位")}
      </section>
    </div>
  `;
}

function renderFolio() {
  return `
    ${metadata()}
    ${gateMessage()}
    <div class="proposal-shell">
      <header class="folio-nav"><a href="./index.html">預覽提案</a><a href="#signup">報名</a></header>
      <section class="folio-hero">
        <div class="folio-strip">
          <img src="${assets.process_01}" alt="課堂色鉛筆練習過程">
          <img src="${assets.hero_work}" alt="小黃金色鉛筆作品">
          <img src="${assets.process_02}" alt="學員描繪寵物毛流">
        </div>
        <div class="folio-title">
          <p>${proposal.proposal_name}｜${proposal.primary_template_id} + ${proposal.secondary_template_id}</p>
          <h1>${course.course_title}</h1>
          <p>${course.course_subtitle}</p>
        </div>
      </section>
      <section class="folio-about">
        <span>關於課程</span>
        <h2>把課程拆成一本可翻閱的作品專刊。</h2>
        <p>本款以 TPL-008 的雜誌式作品集為主要視覺，再用 TPL-007 的作品展示秩序整理課程成果與報名資訊。</p>
      </section>
      <section class="folio-gallery" aria-label="作品展示">
        <figure class="cover"><img src="${assets.work_collage}" alt="寵物色鉛筆作品集合"><figcaption>作品集合</figcaption></figure>
        <figure><img src="${assets.work_duck}" alt="鴨鴨貓色鉛筆作品"><figcaption>指定練習</figcaption></figure>
        <figure><img src="${assets.work_box}" alt="寵物肖像進階作品"><figcaption>肖像延伸</figcaption></figure>
        <figure><img src="${assets.work_gray}" alt="灰白貓色鉛筆作品"><figcaption>毛色細節</figcaption></figure>
      </section>
      <section class="folio-cta" id="signup">
        <div><p>報名頁</p><h2>填寫資料，預約本期小班席位。</h2></div>
        ${formBlock("送出報名")}
      </section>
    </div>
  `;
}

const renderers = {
  air: renderAir,
  brand: renderBrand,
  folio: renderFolio
};

root.innerHTML = (renderers[proposal.variant] || renderAir)();

if (state.isExpired || state.isNotSelected) {
  root.querySelector(".proposal-shell")?.setAttribute("hidden", "");
}

root.querySelector("[data-preview-form]")?.addEventListener("submit", (event) => {
  event.preventDefault();
  const message = state.isSelected
    ? "此款已被選定，可進入正式製作流程；preview 表單不會送出真實報名。"
    : "這是預覽頁，表單不會送出真實報名。";
  window.alert(message);
});
