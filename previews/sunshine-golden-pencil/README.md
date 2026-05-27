# Sunshine Golden Pencil Preview Proposals

This directory contains customer-facing preview pages for the `sunshine-golden-pencil` course project.

These pages are previews only. They do not replace the formal `sunshine-golden-pencil` page.

## Preview Routes

- A: `/previews/sunshine-golden-pencil/proposal.html?proposal_id=A&expires_at=2026-06-30T23%3A59%3A59%2B08%3A00`
- B: `/previews/sunshine-golden-pencil/proposal.html?proposal_id=B&expires_at=2026-06-30T23%3A59%3A59%2B08%3A00`
- C: `/previews/sunshine-golden-pencil/proposal.html?proposal_id=C&expires_at=2026-06-30T23%3A59%3A59%2B08%3A00`

## Query Parameters

- `proposal_id`: `A`, `B`, or `C`.
- `expires_at`: ISO datetime string. If now is later than this value, the preview renders as expired.
- `preview_status`: optional override. Use `expired` to force the expired state.
- `selection_status`: optional override. Use `selected` or `not_selected` after the client chooses one proposal.

## Data Notes

`canva_url` is present in `proposals.json` but intentionally uses `CHAT_A_CANVA_URL_*_REQUIRED` placeholders because no real Chat A Canva links are stored in this repo yet. Replace those values before production use.
