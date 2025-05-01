Here's a summary of the database changes based on everything we've discussed:

### **Table Structure Changes**

2. **New Tables for Better Data Structure**
   - **`payment_methods`** (instead of storing in `site_configurations`)
     - `id` (PK)
     - `label` (string)
     - `value` (string, unique)
     - `user_option` (boolean) – whether the user can choose this method

   - **`donation_levels`** (instead of storing in `site_configurations`)
     - `id` (PK)
     - `label` (string)
     - `value` (string, unique)
     - `amount` (integer)
   
   - **`donation_perks`** (one-to-many with `donation_levels`)
     - `id` (PK)
     - `donation_level_id` (FK → `donation_levels.id`)
     - `perk` (string)

   - **`patron_payment_ids`** (instead of `patrons.fixr_user_uuid`)
     - `id` (PK)
     - `patron_id` (FK → `patrons.id`)
     - `payment_method_id` (FK → `payment_methods.id`)
     - `external_id` (string) – ID from Fixr, PayPal, etc.

3. **Fix Relationships That Were Missing**
   - **One-to-Many:** `patrons → fixr_webhook_responses`
   - **One-to-One:** `ticket_purchases → tickets`
     - Either `Ticket` model or `TicketPurchase` model can use `belongsTo()`, but `TicketPurchase` likely makes more sense.
   - **One-to-Many:** `donation_levels → donation_perks`

### **Other Considerations**
- **No direct relationship between `users` and `patrons`** (since overlap exists but isn’t needed in DB).
- **No separate table for printed tickets yet**, unless needed in the future.
- **Fixr webhook data remains mostly an archive**, so minimal changes except marking `payload` as `json`.

Let me know if anything needs adjusting! 🚀
