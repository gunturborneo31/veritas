# Board brief: Kitoo

This brief stands alone. The board has not seen any other conversation.

# Kitoo (kitoo.my.id)

- What it is: An Indonesian web platform where a buyer picks a ready-made template, fills it in a no-login editor ("Studio"), previews it with a watermark, pays, and gets a permanent link + QR code to a personal interactive digital greeting or event invitation, opened by the recipient on their phone.
- Who it is for: Individuals in Indonesia, mostly on mobile, who want to give a personal, memorable greeting for a moment (birthday, anniversary, partner, family, Lebaran/hari raya, apology, "nembak"/asking someone out, graduation, thank-you, missing someone) without design or coding skills, and are willing to pay a small amount (under Rp100.000). A second group: families hosting events (wedding, engagement, aqiqah, khitanan, pengajian, syukuran, opening/peresmian) who need a digital invitation sent over WhatsApp. Recipients never need an account.
- What it sells, at what price: One-off payment per greeting/invitation, one payment = one page for one recipient, no free tier (watermarked preview instead), content frozen after payment (no edits). Prices currently set in the catalog: greetings Rp25.000–Rp59.000 (most at Rp25.000–Rp35.000; "Klasik" and "Interaktif" tiers); event invitations Rp39.000–Rp89.000 (wedding Rp79.000). A "Paket 2" (choose visual style per component) is planned at a higher, not-yet-set price.
- Where and how: Online only, at kitoo.my.id. Shared to recipients via WhatsApp link or QR code. Payment by QRIS (dynamic QRIS via API, or static QRIS confirmed manually by an admin as fallback). How customers will find it: not decided yet.
- Budget and constraints: Product is built (Laravel + React/Inertia), by a solo developer. Marketing budget, monthly costs and sales targets: not stated yet. Licensed music library only (no user-uploaded songs in release 1).

## Additional facts from the founder's product document and code

- Positioning stated by the founder: direct comparable is Lovio.id (interactive digital gifts in Indonesia). Intended differentiators: wider range of moment categories (not only couples), a future group mode where many senders contribute to one greeting, and a future per-component visual style choice (Paket 2).
- Interactive components available: cover, PIN lock / secret question gate, time lock (opens at a set date/time), photo, swipe gallery (3–12 photos), letter, layered messages, background music (internal licensed library), voice message, blow-out-the-candles (uses the phone microphone), quiz ("how well do you know me"), scratch card, promise coupons, countdown, photo timeline, map of memory places, "our song" (Spotify/YouTube link), downloadable certificate, yes/no question ("will you be my girlfriend?"), reasons list, "us in numbers", closing + reply button for the recipient.
- Event invitation components: couple details with parents' names, child details (for aqiqah/khitanan), event schedule, RSVP, digital envelope (gifts), guest wishes wall, guest list with a personal link per guest.
- Catalog: about 40+ templates across ~20 categories (ulang tahun, pasangan, keluarga, hari raya, minta maaf, nembak, wisuda, terima kasih, kangen, kehamilan, aqiqah, khitanan, lamaran, pengajian, peresmian, pernikahan, pesta, syukuran).
- Funnel the founder plans to measure: drafts created per week, draft → preview → paid rate, share of recipients who actually open, time to finish a greeting.
- Later phases planned: automatic payment gateway (VA, e-wallet), more templates and mini-games, group mode, AI-generated characters from photos, digital guest book.
- Unknown so far (no data yet): number of paying customers, conversion rate, customer acquisition cost, market size, monthly operating costs, marketing budget, competitor prices.
