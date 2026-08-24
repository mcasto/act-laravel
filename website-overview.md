# ACT Website — What's Working, What's Not (updated 2026-08-24)

A non-technical overview of the site's features, grouped by how solid each piece actually is.

## Fully working, start to finish

- **Shows & performances** — creating shows, scheduling performance dates/times, public listings.
- **Ticket sales** — box-office entry, admin editing, revenue reports, no-show tracking.
- **Fixr ticket sales** — real purchases through Fixr get automatically recorded, tagged as "Credit Card" (Fixr is just the processor, not its own payment method, so it stays accurate if you ever switch processors).
- **Auditions** — public sign-up, admin review.
- **Courses/Classes** — public listing and sign-up, admin management with photos.
- **Photo gallery** — admin upload/reorder, public display.
- **Contact form** — visitor submissions are saved and emailed to you; you can browse/delete them in admin.
- **"Quick Messages"** (formerly "Message Us") — the simpler contact widget now saves every submission and has its own admin page to browse/delete them, matching how Contacts already worked.
- **Support Us page** — a donation page taking Fixr/PayPal payments (note: purely a payment page, nothing about who gave gets saved inside the app).
- **Site settings** — default ticket price, sold-out threshold, contact emails, plus the content editors for Season, Support Us, and Flex, all in one settings screen.
- **Payment Methods admin page** — a real screen to add/edit/delete payment methods, including a color picker, instead of needing direct database access.
- **Payment instruction templates** ("Payment Methods" tab inside Site Settings — a *different* thing from the payment-methods admin page above, despite the shared name) — editable text for how each payment option is explained to a buyer.
- **Site-wide announcement banner** — toggle on/off, rich text.
- **Angels donor program** — donation levels, individual donor records, a benefits list per level, and now a real "Donate" flow: the public form actually creates a donor record and emails you, instead of just showing payment instructions with nothing captured. A Fixr donation for an Angel level is also automatically recorded, the same way Fixr ticket sales are. "Founding Angel" status is automatically carried forward for a donor's future donations once it's been set once.
- **Active Angel Season** — a setting (Admin → Site Settings → Season) for which season new Angel donations get tagged with, separate from the show/Flex-ticket calendar — since Angel promotion for a season starts before the previous one technically ends.
- **Flex ticket redemption** — spending an already-purchased flex package at a specific show.
- **Flex early-access links** — a shareable link letting select patrons buy before the public. One limitation: only one show's link can be active at a time.
- **Comp (free) tickets** — fully built end to end, though per your own note it's never actually been used by a real cast/crew member yet.
- **User accounts** — self-service profile editing, with an owner-only lockdown on editing/deleting other users.

## Partly built — real, usable gaps

- **Volunteer sign-ups**: the public form works and emails you, but nothing gets saved to a browsable list. The admin page to manage volunteers is actually already built in the code — it's just switched off (disconnected from the menu), so turning it on is a small fix, not a rebuild.
- **Buying a flex ticket package**: the purchase page and its settings work, but there's no actual "process this purchase" step behind it. Every package today gets entered by hand, once a season, from a spreadsheet.
- **Patrons / Donations / Payments admin pages**: empty placeholders, no content at all. "Donations" in particular looks like a separate donor-tracking idea that was started (there's database structure for it) and then abandoned in favor of the Angels program.
- **"About Us" / "Find Us" page text**: can only be changed by editing a file directly on the server — no in-app editor.
- **A "update Fixr link" backend endpoint** exists (`update-fixr-link`) but nothing in the site actually calls it — there's no button or form anywhere connected to it. It's leftover scaffolding for a feature that was never built.

## Not started, but not actually a problem

- **"Join Mailing List" page**: literally just placeholder text, never built. Doesn't matter in practice — the real "Join Our Mailing List" link visitors see goes straight to an external Mailchimp signup, bypassing this page entirely.
- A handful of leftover empty files (a few unused controllers, some Laravel starter-kit boilerplate) — no effect on the site, just clutter in the codebase.

## Suggested next step

If any of the "partly built" items are worth closing the gap on, the volunteer-admin one is still the easiest win — it sounds like it just needs its menu link turned back on.
