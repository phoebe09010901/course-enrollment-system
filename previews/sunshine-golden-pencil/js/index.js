const data = window.SUNSHINE_PREVIEW_DATA;
const list = document.querySelector("#proposalList");

list.innerHTML = data.proposals.map((proposal) => {
  const previewUrl = `./proposal.html?proposal_id=${proposal.proposal_id}&expires_at=${encodeURIComponent(data.default_expires_at)}`;
  return `
    <article class="proposal-card proposal-card--${proposal.variant}">
      <div>
        <p>${proposal.proposal_code}</p>
        <h2>${proposal.proposal_id} 款｜${proposal.proposal_name}</h2>
        <p>${proposal.direction}</p>
      </div>
      <dl>
        <div><dt>主要模板</dt><dd>${proposal.primary_template_id} ${proposal.primary_template_name}</dd></div>
        <div><dt>輔助模板</dt><dd>${proposal.secondary_template_id} ${proposal.secondary_template_name}</dd></div>
        <div><dt>Canva</dt><dd>${proposal.canva_url}</dd></div>
      </dl>
      <a href="${previewUrl}">開啟預覽</a>
    </article>
  `;
}).join("");
