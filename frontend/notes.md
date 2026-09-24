# How Ticket Sales Work

## The core record: `TicketSale`

`app/Models/TicketSale.php`, table `ticket_sales`. One row = one purchase transaction (not one seat) — `quantity` says how many seats it covers.

Fields: `patron_id`, `payment_method_id`, `performance_id`, `transaction_id` (a short ref code via `RefId::ref_id()`), `transfer_date`, `sold_at`, `quantity`, `no_show`, `confirmed`, `reason_changed`, `special_seating`.

- **`confirmed`** — "payment confirmed" checkbox. Defaults to true automatically when payment method is comp or flex (both are effectively pre-paid/no-cost), editable manually otherwise.
- **`no_show`** — toggled at the door on `/admin/ticket-sales`.
- **`reason_changed`** — free-text note, only editable/shown on the Edit form, for recording why a sale was modified after the fact.
- **`special_seating`** — reserved seats for this sale's *whole party* (0–20), e.g. an Angel donor level that grants reserved seating for their group. Distinct from `Patron.front_row` (below) — see that section for how the two combine on the print sheet.

## Patrons tie in via email, everywhere

`app/Models/Patron.php`, table `patrons`. Every code path that creates a `TicketSale` (or `Angel`, or `PatronFlexPackage`) resolves the patron the same way:

```php
Patron::firstOrCreate(['email' => $email], ['first_name' => ..., 'last_name' => ..., 'phone' => ...])
```

**Email is the de facto dedup key** across the whole app, even though the `patrons` table has no DB-level unique constraint on it (an old design gap — kept loose for all the `firstOrCreate` call sites that predate stricter validation). The new Patron Management admin CRUD (`/admin/patrons`) *does* enforce uniqueness on create/edit, so as of now it can't make the problem worse, but plenty of old rows could theoretically still collide.

Patron-level fields that matter for ticketing:
- **`front_row`** (0–3) — a *standing* accessibility accommodation (wheelchair, vision-impaired, caretaker seats), not tied to any one sale. Edited inline on `/admin/patrons`.
- **`comments`** — admin-only free-text notes (`LONGTEXT`), e.g. "usually ~15 min late."
- **`founding_angel`** — permanent, sticky flag. Once true for a patron, it's true on every past/future Angel record for them regardless of what any single form submits (see `AngelController::resolveFoundingAngel()`).
- **`ticketSales()`** / **`flexPackages()`** relations — also what blocks a patron from being deleted (see CRUD section below).

A ticket sale's own `first_name`/`last_name`/`email`/`phone` on the *edit* form actually just edit the linked Patron record (there's no separate copy on `TicketSale` itself) — so renaming the buyer on one sale renames the Patron everywhere else they appear too. This is expected/existing behavior, not a bug.

## Individual seats: `Ticket`

`app/Models/Ticket.php`, table `tickets`. One row per physical seat/ticket number, `belongsTo(TicketSale)`. `number` is drawn from a **per-show shared sequence** (`Show.next_ticket_number`, incremented atomically in `Show::reserveTicketNumbers()`) — so ticket numbers are unique within a show across *every* sale and comp for that show, not per-sale.

`name` on a Ticket is the individual attendee's name for that seat (the "Tickets" section on the ticket-sale form) — defaults to the purchaser's name for ticket #1, and to its own zero-padded ticket number as a placeholder for any other seat nobody's named yet. `formatted_number` (e.g. `"007"`) is used everywhere to detect "still a placeholder, not a real guest name."

## Comp tickets are a *different table*, not a payment-method flag on TicketSale

`app/Models/CompTicket.php`, table `comp_tickets`. **No `patron_id` column at all** — only `name` (a single string, not split first/last), `email`, `pickup_name`, `show_id`, `performance_id`, `number`, `uid`, `sent_at`/`redeemed_at`.

Two ways a comp ticket gets created:
1. **`/admin/ticket-sale-new` with Payment Method = "Comp"** (the normal path now) — `TicketSaleController::store()`'s comp branch. This *does* resolve/create a real `Patron` by email first (so first/last name are captured properly), then squashes them into `CompTicket.name` as a single string before saving — the print sheet used to just read that squashed string back out for the Last/First Name columns, splitting it wrong (e.g. "Deana Culp" landing whole in First Name). **Fixed 2026-09-24**: the print sheet now looks the matching Patron back up by email and uses its real split `first_name`/`last_name`, falling back to the squashed name only if no matching Patron exists.
2. **The older standalone Comp Tickets admin flow** (`CompTixController`) — a legacy per-show "send a comp ticket by name+email" tool where `name` is genuinely meant to be one free-text field (could be "John & Jane Smith," anything). No Patron link, no separate first/last ever existed here.

`pickup_name` is captured separately, at redemption time (`CompTixController::redeemComp()`) — it's whoever actually shows up at the door with the comp code, which may not be the person it was originally issued to. Comp tickets are **transferable**, same as Flex (see below).

`TicketSaleController::allSales()` merges real `TicketSale` rows and synthetic `CompTicket` rows into one list for the admin table/print sheet — comp rows get `payment_method.value === 'comp'` synthesized in, `quantity` always 1, and no `tickets[]` breakdown (just a single `number`).

## Flex tickets ("5 admissions for the price of 4" packages)

`app/Models/PatronFlexPackage.php`, table `patron_flex_packages`. A *purchase* of a bundle (`tickets_purchased`) tagged to a `season` string (e.g. `"26-27"`). Usage isn't tracked on the package itself — `ticketsRemaining()` recomputes live: total purchased for that patron+season minus the sum of `quantity` on all their `TicketSale` rows where `payment_method.value === 'flex'` and the performance date falls inside that season's date range (`TheaterSeason::datesForSeason()`).

So a Flex *ticket sale* is a completely normal `TicketSale` row (real patron, real quantity, real tickets issued) — the only thing marking it as "flex" is `payment_method_id`. Like comp, flex tickets are **transferable**: the patron of record stays in Last/First Name, but the person who actually shows up (per-ticket name in the Tickets section) goes in the Guest List column on the print sheet.

Comp/flex terminology used in this codebase: "**-er**" = the patron of record (comper/flexer, stays in Name columns), "**-ee**" = the actual door attendee (compee/flexee, goes in Guest List). The print sheet highlights comp rows and flex rows in two different dark colors (readable in grayscale too) instead of writing "comped for..." text.

## Angels tie in for concession perks, not tickets directly

`app/Models/Angel.php` — a donation record, `belongsTo(Patron)` and `belongsTo(AngelLevel)`. Not a ticket sale itself, but `TicketSaleController::allSales()` cross-references it: for every real `TicketSale`, it looks up that patron's Angel record **for the current calendar season** (`TheaterSeason::currentString()` — deliberately *not* the admin's early-flip `ActiveSeason` override, since a show happening now is always the real current season regardless of that promo flip) and lists which of their level's benefits are flagged `concession: true` (e.g. free drink/snack coupons). Shown as the "Angel Benefits" column on the print sheet.

## The "Special Seating" print column = two different sources added together

`front_row` (patron-level accessibility need, max 3) + `special_seating` (sale-level party reservation, max 20) are **summed** into one print-sheet column. Deliberately simple — a patron needing both at once is rare enough that exact-right handling isn't worth the complexity yet; revisit if that combination ever actually causes a problem at the door.

## Payment methods (`payment_methods` table, `value` column)

`paypal`, `transfer` (Ecuadorian bank transfer), `credit_card` (includes Fixr — see below), `comp`, `flex`, `cash`, `raffle`, `trade`, `door`, `walk-in`. Only `comp` and `flex` have special handling described above; the rest are just labels/reporting categories.

## External source: Fixr webhooks

`app/Http/Controllers/FixrWebhooksController.php`, `POST /api/fixr-webhooks` — live in production. Every incoming webhook is logged unconditionally to `storage/app/private/logs/fixr_webhook_*.log` (no rotation, accumulates indefinitely) before any processing, for debugging. Matches the event to either:
- a `Performance` (by its `fixr_link`) → creates a normal `TicketSale` (`payment_method` = `credit_card` — Fixr is just the processor, not tracked as its own method) with `confirmed = true` and no patron-facing email (Fixr sends its own receipt), or
- an `AngelLevel` (by its `fixr_link`) → creates an `Angel` donation instead, or
- the sitewide Flex `fixr_link` → creates a `PatronFlexPackage` purchase.

All three still go through the same `Patron::firstOrCreate(email)` pattern.

## Reservation reminders (currently logging only, not sending)

`app/Console/Commands/SendReservationReminders.php`, `reminders:send`, scheduled daily. For every performance happening tomorrow, would email each distinct patron with a sale (or a *redeemed* comp ticket) for it — currently **disabled** after an erroneous send caused problems; instead it appends what it *would* send to `storage/app/private/reservation-reminder-log.csv` (date, recipient, performance date, quantity) so the selection logic can be verified against real data before re-enabling actual sending.

## Patron Management is now full CRUD

`/admin/patrons` — Create/Edit dialog for name/email/phone/founding-angel, inline editors for front_row/comments, and Delete. **Delete is blocked** (with a specific message naming what's attached and how many, e.g. "...still has 2 ticket sales on record...") if the patron has any ticket sales, Angel donations, or Flex purchases — soft-deleting a patron with history would otherwise make their name silently disappear from those old records (the `SoftDeletes` global scope excludes trashed rows from eager loads). Deletion with no history at all just works — mainly intended for cleaning up test patrons.

## Admin surfaces

- **`/admin/ticket-sales`** (`AdminTicketSales.vue`) — the on-screen table. Column order: Performance Date, Name, Qty, Ticket #s, Confirmed, Date Sold, No Show, Special Seating, then payment-method icon + actions.
- **Add/Edit form** (`AdminTicketSaleForm.vue`) — same component for both. Field order starts with Performance now. Has the "Tickets" section (per-seat names, auto-resizes with quantity), Special Seating input, Payment Confirmed checkbox.
- **Print sheet** (`AdminTicketSalesPrint.vue`, printer icon) — the door/box-office sheet. Exactly 9 columns: Last Name, First Name, # Tickets, Payment Method, Amt Due, Amt Collected, Special Seating, Guest List, Angel Benefits — deliberately *not* the same column set as the on-screen table. Sorted by last name. Comp/flex rows get dark-background highlighting (see Angels/Flex section above).
