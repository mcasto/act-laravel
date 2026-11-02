# October 2026 Commits

| Date | Commit | Description |
|---|---|---|
| 2026-10-05 | d0f97ad | Handled "I bought these for someone else": added a comments field to Angel donations and Flex packages, let admins set a Flex purchase date, and stopped the public Angels and patron-lookup endpoints from exposing private details. |
| 2026-10-07 | 76139f0 | Added an unconfirmed-payments report to Ticket Sales, with payment reminder emails to patrons. |
| 2026-10-09 | 348fdc1 | Added ticket numbers to the subject line of patron emails (purchase confirmation, comp tickets, payment reminders). |
| 2026-10-11 | 947d30f | Flex redemptions now need email confirmation before they're booked. Locked down the public ticket-sale endpoint so it can't set admin-only fields. Converted the remaining MyISAM tables to InnoDB. |
| 2026-10-11 | 96ea17e | Ticket sale form shows the patron's current Angel level and drink/snack benefits, or notes that they're not an Angel under that email. |
| 2026-10-11 | 64d75a1 | Linked older current-season Angel records to their patrons, showed the patron email on the Angel edit dialog, and rewrote the README. |
| 2026-10-11 | 1889a7a | Added edit and delete to the Angels by Season table, and fixed the delete confirmation that said "undefined". |
| 2026-10-13 | f89bc0f | Made credit/debit card the first payment option on the class enrollment form. |
| 2026-10-14 | 3691204 | Fixed the links in the Flex confirmation email. |
| 2026-10-14 | d84a288 | Made Flex email confirmation a setting and turned it off, so Flex redemptions book immediately again. |
| 2026-10-14 | 30be5f3 | Added ticket number search and a payment method filter to Ticket Sales. |
| 2026-10-14 | 2e24235 | Stopped duplicate comps: one comp per person per show unless the admin ticks "Issue an additional comp". |
| 2026-10-14 | 89b9cde | Deleting a comp ticket sale now returns the comp to the person as unused and releases its ticket number. |
| 2026-10-14 | 8bbb146 | Admins can manually add an enrollee to a class. |
| 2026-10-16 | eab7df2 | Changed the unconfirmed report to show every unconfirmed payment (reverted the same day). |
| 2026-10-16 | d3e777d | Reverted the previous change. |
| 2026-10-16 | 6b36880 | Updated the unconfirmed-payments dialog in Ticket Sales and reformatted the Changelog page (one row per changed field, with search). |
| 2026-10-18 | 1afff6c | Projected Revenue now includes unconfirmed sales and shows each payment method's actual price per ticket. |
| 2026-10-24 | 6580d2a | Gallery admin shows the date the gallery goes public (the day after the final performance). |
| 2026-10-26 | 4e4a607 | Door printout only shows Angel benefits when the Angel is one of the sale's ticket holders. |
| 2026-10-31 | c266440 | Classes: edit and delete enrollees, fixed the enrollee list hiding anyone past the 5th, and disabled Enroll Now when a class is full. |
| 2026-10-31 | 28cf465 | Purchase forms now say "Your First Name" / "Your Last Name". |
| 2026-10-31 | fb0d999 | Every outgoing email is now logged, with a read-only Mail Log page in admin. |
