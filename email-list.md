# Patron-Facing Emails

Every email a **patron** (as opposed to admin/box-office/instructor notifications) can receive, and what triggers it.

## Ticket purchases

1. **Purchase confirmation** — `PurchaseConfirmationMailer` (view `purchase-confirmation`)
   Any non-comp ticket sale (cash, PayPal, transfer, door, etc.), triggered from `TicketSaleController::store()`. Fires for both the admin's "New Ticket Sale" form and the public self-checkout form, as long as the "Send Mail" flag is on (defaults true).

2. **Flex ticket confirmation** — `PurchaseConfirmationMailer` (view `flex-confirmation`)
   Same trigger as #1, specifically when payment method is `flex` (i.e., redeeming a flex ticket for a show). Includes their remaining flex balance for the season.

3. **Comp ticket — initial invite** — `CompTicketMailer` (view `comp-ticket-notice`)
   When an admin clicks "Send" on a newly-issued comp ticket (`CompTixController::send()`), containing the redeem link.

4. **Comp ticket — redemption confirmation** — `CompTicketMailer` (view `comp-ticket-confirm`)
   When a comp ticket is redeemed, either via the public self-redeem page or the box-office "New Ticket Sale → Comp" flow (`CompTixController::redeemComp()`), if send-mail wasn't suppressed.

## Flex packages

5. **Flex package purchase confirmation** — `FlexPurchaseConfirmationMailer`
   Buying the 5-for-4 flex bundle itself via the public Flex Purchase form (`FlexPurchaseController::store()`). Distinct from #2 — that's *using* a flex ticket, this is *buying* the package.

## Angel donations

6. **Angel donation confirmation** — `AngelDonationConfirmationMailer`
   A public donation via the Angel "Donate" form (`AngelController::donate()`). An admin manually adding an Angel record (`store()`) sends nothing to the donor.

## Courses

7. **Course enrollment confirmation** — `CourseInquiryConfirmationMailer`
   Enrolling in a class via the public course form (`CourseController::store()`), regardless of whether it's a free or paid class.

## Sold-out notifications

8. **Sold-out notification** — `SoldOutNotificationMailer`
   Sent to everyone on a separate opt-in subscriber list (`SoldOutNotificationRecipient`, not necessarily the same people as ticket-buying patrons), only when an admin manually triggers it after confirming which performances just sold out (`PerformanceController::sendSoldOutNotifications()`) — never automatic.

## Exceptions

- **Fixr webhook purchases** (credit card checkout via Fixr) never send a patron-facing email for any of the above — tickets, angel donations, flex packages, or course enrollments. Fixr itself sends the buyer's receipt as the payment processor; only an internal box-office notification goes out.
- **Reservation reminders** (`ReservationReminderMailer`) exist as a Mailable but are not currently firing. `SendReservationReminders` (the scheduled command) only logs what it *would* send to a CSV right now — disabled after an earlier erroneous send, not yet re-enabled.
