# ACT Seats

The website for **ACT, the American Community Theatre** in Cuenca, Ecuador, live at
[actseats.com](https://actseats.com). The public site sells tickets, Flex packages and
Angel donations, and handles classes, auditions and volunteers. Behind it, the admin
area runs the box office.

## What the site does

### Public site
- **Shows and tickets:** current and upcoming shows, performance dates, posters with
  sold-out banners, and ticket purchase by credit card (Fixr), PayPal, bank transfer
  or a Flex package.
- **Flex packages:** patrons buy a season package of tickets, then redeem them one
  show at a time. A Flex redemption is held until the patron confirms it from a link
  emailed to the address on file.
- **Angels:** donor levels with their benefits, donation through Fixr, and a public
  thank-you list for the current season.
- **Comp tickets:** cast and crew receive an emailed link to claim their comp ticket.
- **Classes, auditions and volunteering:** class details and enrollment during the
  enrollment period, audition information, and volunteer sign-up.
- **Galleries, news and mailing list:** show galleries appear after a show closes.
  Patrons can also sign up for sold-out notifications.

### Admin area
Each section has its own permission level: full, read-only or none.
- **Ticket sales:** add, edit and delete sales, with ticket numbers, door names,
  front-row and special seating, and comments. Also a payment-confirmed flag, an
  unconfirmed-payments report with reminder emails, summaries, and a printable door
  sheet that includes Angel concession benefits.
- **Patrons, Flex purchases and Flex usage**, with CSV exports.
- **Angels:** levels, donations by season, and Founding Angel status.
- **Shows, performances, comps, galleries, classes, auditions, volunteers, contacts
  and project charters.**
- **Site config:** announcement banner, confirmation-email templates, Angel and Flex
  settings, and payment methods.
- **Change log:** a record of admin actions.

## Tech stack
- **Backend:** Laravel 12 (PHP 8.2+) API on MySQL/MariaDB, with every table on InnoDB.
- **Frontend:** a Quasar 2 / Vue 3 single-page app in [`frontend/`](frontend/).
- **Hosting:** shared LiteSpeed hosting (cPanel). There are no queue workers, so mail
  is sent synchronously. The scheduler runs from a single cron entry.
- **Email:** MailerSend.
- **Payments:** Fixr processes card payments. Its webhooks record ticket sales,
  Flex packages, Angel donations and class enrollments. PayPal and bank transfers
  are confirmed manually in the admin.

## Repository layout

| Path | What's there |
| --- | --- |
| `app/Http/Controllers` | API controllers |
| `app/Models` | Eloquent models |
| `app/Helpers` | Season helpers (`TheaterSeason`, `ActiveSeason`), `PatronMail`, `RefId` |
| `app/Mail`, `resources/views` | Mailables and their Blade email templates |
| `app/Console/Commands` | Artisan commands, including scheduled maintenance |
| `routes/api.php` | All API routes. Admin routes use `auth:sanctum` and `permission:<section>` |
| `frontend/src` | The Quasar SPA: `pages/`, `components/`, `router/`, `stores/` |
| `storage/app/private` | JSON config files edited from the admin (Angels, Flex, etc.) |

## Local development

```bash
composer install
# Create .env (not in the repo; .env* is gitignored). Set DB_*, MAIL_*,
# FRONTEND_URL and CC/CC_EMAIL.
php artisan key:generate
php artisan migrate

cd frontend
yarn install
yarn dev                  # Quasar dev server, proxies /api to the Laravel site
```

The dev server proxies API calls to the local Laravel site configured in
`frontend/quasar.config.js`.

Patron email addresses in a copy of the production database are real. Keep `MAIL_MAILER`
pointed somewhere safe, or use `Mail::fake()` when testing.

## Deployment

- **Backend:** run `git pull` on the server, then:
  - `composer install` if dependencies changed;
  - `php artisan migrate` if there are new migrations (back up the database first);
  - `php artisan config:clear`.
- **Frontend:** run `yarn deploy` in `frontend/`. It builds the SPA and rsyncs
  `dist/spa/` to the server.
- **Preview sandbox:** `yarn deploy-preview` deploys the frontend to the preview site.
  The preview backend tracks a feature branch, for example `party_names`.

## Scheduled tasks
Defined in [`routes/console.php`](routes/console.php), in Cuenca time:
- `reminders:send` (daily at 10:00): reservation reminders. This is currently in
  verification mode and logs instead of sending.
- `storage:cleanup-orphans` (daily at 03:00): removes uploaded files that nothing
  references.

## Conventions worth knowing
- **Seasons:** `TheaterSeason::currentString()` is the real season, for example `26-27`.
  `ActiveSeason` can switch early so that *new* donations and purchases are tagged
  with the coming season. Reports and lookups use `TheaterSeason`.
- **Times:** the app runs in UTC. Performance dates and times are local Cuenca time
  (`America/Guayaquil`).
- **Patron emails** go through `App\Helpers\PatronMail`. It CCs the address in
  `CC_EMAIL` when `CC` is on, unless the patron is that address.
- **Public endpoints never trust admin-only fields.** Admin entry has separate
  authenticated routes.
- **Database tables use InnoDB.** The production host defaults to MyISAM, so
  `config/database.php` sets InnoDB explicitly.

---

## Changelog

Built from the git history and grouped by month, newest first. Small fixes are folded
into the entries around them.

> **Note on older data:** many features were added partway through the 2025-26
> season, including patron links on Angels, ticket numbers, door names, Flex package
> records and payment confirmation. **Records from before the 2026-27 season may be
> incomplete or inconsistent.** For example, every 2025-26 Angel record is unlinked:
> it isn't tied to a patron, so it shows no email and isn't matched to ticket sales.
> Current-season data has been corrected. Older seasons were deliberately left as
> they are, so if you look up older records by hand, check them against the patron
> records.

### October 2026
- README rewritten for the actual site, with this changelog.
- Linked current-season Angel records that were entered before Angels had patron links,
  so their benefits show on ticket sales.
- The Angel edit dialog shows the linked patron's email, or warns when a record isn't
  linked to a patron.
- Ticket sale form shows the purchaser's Angel level and concession benefits.
- Flex redemptions require the patron to confirm from an emailed link before tickets
  are used. The Flex balance is enforced on the server.
- Hardened the public ticket-sale endpoint so admin-only fields (confirmed, send mail,
  seating, comp) can't be set from outside.
- Converted the remaining MyISAM tables to InnoDB and restored the foreign keys on
  `tickets`.
- Patron emails no longer fail when the patron is also the CC address.
- Ticket numbers added to the subject line of patron-facing emails.
- Unconfirmed-payments report on ticket sales.
- Angels and Flex handle "I purchased these for someone else."

### September 2026
- Ticket sales:
  - redeemable ticket IDs and door names;
  - front-row and special seating, and comments;
  - a seating summary on the add and edit forms;
  - a door printout with comp and Flex rows color-coded;
  - a payment-confirmed flag on the admin form, and an optional phone number;
  - a "send email" toggle for new sales.
- Patrons: full create, edit and delete. Flex purchases: create, edit, delete and CSV
  export. A Flex usage section, including usage by patron.
- Angels: CSV export, a reworked add/edit form, and Founding Angel status.
- Class admin and the class preview. Enrollment only during the enrollment period.
- Auditions and volunteer needs moved up to top-level admin sections.
- Project charters.
- Patron-facing emails can be CC'd to a monitoring address.
- Cascading soft deletes fixed. Admin permission sections are listed alphabetically.
- Fixes: login, season changeover, gallery posters, the performance dialog, and an
  orphaned database row that blocked the classes admin.

### August 2026
- Admin permission levels per section.
- Patrons added to the admin. Angels are added as patrons when they aren't found.
- Angels: by-season view with search and dates, correct donation amounts recorded, and
  the Angel email included in admin notifications.
- Ticket sales: confirmed field and table summaries.
- "SOLD OUT" notification sign-ups and management.
- Next season's info appears only after the season's final performance. Galleries
  appear only after a show has finished.
- Admin change log.
- Confirmation-email template editors in site config.
- Fixr webhook logging and manual sync. "Credit card" is recorded as the payment method
  for Fixr.
- One-off Google Sheet sync for box-office Flex data (2025-26, since retired).
- Reservation reminder emails disabled pending verification.
- Preview deployment and environment. Updated logo. Loading spinner in the admin.

### July 2026
- Flex: early purchase link, and multiple packages per patron fixed.
- PayPal and transfer forms ask for a payment screenshot. Free-form fields added.
- API calls time out with a "please turn off your VPN" alert.
- Volunteer page moved to a Google Form.
- Fixr links color-coded on performances.
- Performance and speed improvements.

### June 2026
- Backend caching, image optimization on upload, and a scheduled storage-cleanup task.
- Site icons switched to SVG.
- Search on admin contacts. Fixr field on Our Angels.
- Home page and upcoming-shows carousel improvements. A placeholder image for the next
  season.

### May 2026
- Classes completed.
- Rich-text editors in admin site config.
- Ticket sales page cleanup.

### April 2026
- Purchase options collect the required information.
- Comp ticket confirmation page and comp handling.
- Confirmation and reminder emails.
- Ticket sales admin finished, with a table and summary. Box-office paper sales can be
  entered without sending emails.
- Sold-out banner on poster images.

### January–February 2026
- Shows admin display and navigation fixes. Angels updates.
- Audition link on the upcoming-shows carousel.

### December 2025
- Courses and classes admin, including delete.
- Fixr webhook updates. Show poster update fix.

### November 2025
- Site complete apart from classes. Gallery admin.
- Our Angels. Standard purchase buttons moved to Blade templates.
- Preview released. Fixes after review meetings.
- Fixr webhook handler for ticket sales. A ticket sales admin draft.

### September 2025
- Home page, shows and performances admin.
- Flex purchase page. Fixr integration.
- Purchase tickets page and purchase options.
- Announcement banner admin. Mailing-list sign-up.
- Volunteers admin and contacts admin.

### May 2025
- Initial commit.
