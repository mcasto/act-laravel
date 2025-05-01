### **1. Independent Tables (No Foreign Keys)**
These tables can be created first since they have no dependencies.

1. **users**
   - id
   - name (string)
   - email (string)
   - email_verified_at (date)
   - password (string)
   - remember_token (string)
   - created_at
   - updated_at

2. **payment_methods**
   - id
   - label (string)
   - value (string, unique)
   - user_option (boolean)

3. **mailchimp_lists**
   - id
   - name (string)
   - mailchimp_id (string, unique)

4. **donation_levels**
   - id
   - label (string)
   - value (string, unique)
   - amount (integer) [cents]

5. **shows**
   - id
   - name (string)
   - writer (string)
   - tagline (string)
   - director (string)
   - poster (string)
   - ticket_sales_start (date)
   - slug (string)
   - created_at
   - updated_at
   - deleted_at

6. **site_configs**
   - id
   - config (json)
   - created_at
   - updated_at
   - deleted_at

---

### **2. Dependent Tables (With Foreign Keys)**
These tables reference the independent tables and should be created after their respective parent tables.

7. **mailchimp_members** (Depends on `mailchimp_lists`)
   - id
   - mailchimp_list_id (FK → mailchimp_lists.id)
   - email (string, unique)
   - status (string, default 'subscribed')
   - merge_fields (json, nullable)

8. **patrons** (Depends on `mailchimp_members`)
   - id
   - name (string)
   - mailchimp_members_id (FK → mailchimp_members.id)
   - email (string)
   - wn_phone (string)
   - fxr_user_uuid (string)

9. **patron_payment_ids** (Depends on `patrons` and `payment_methods`)
   - id
   - patron_id (FK → patrons.id)
   - payment_method_id (FK → payment_methods.id)
   - external_id (string)

10. **donations** (Depends on `patrons` and `donation_levels`)
    - id
    - patron_id (FK → patrons.id)
    - amount (integer) [stored as cents]
    - donation_level_id (FK → donation_levels.id)
    - date (date)

11. **donation_perks** (Depends on `donation_levels`)
    - id
    - donation_level_id (FK → donation_levels.id)
    - perk (string)

12. **performances** (Depends on `shows`)
    - id
    - show_id (FK → shows.id)
    - date (date)
    - sold_out_forecast (int)
    - fxr_link (string)
    - created_at
    - updated_at

13. **tickets** (Depends on `performances`, `payment_methods`, and `users`)
    - id
    - performance_id (FK → performances.id)
    - purchaser_name (string)
    - purchaser_email (string)
    - purchaser_phone (string)
    - assigned_name (string)
    - num_tickets (int)
    - payment_method_id (FK → payment_methods.id)
    - order_date (date)
    - payment_date (date)
    - purchased_by (FK → patrons.id)
    - fxr_ticket_id (int)
    - fxr_response (longText)
    - fxr_user_uuid (string)
    - created_at
    - updated_at

14. **contacts**
    - id
    - from_name (string)
    - from_email (string)
    - wn_phone (string)
    - subject (string)
    - body (text)
    - created_at
    - updated_at
    - deleted_at

15. **fxr_webhook_responses** (Depends on `users`)
    - id
    - patron_id (FK → patrons.id)
    - event (string)
    - payload (json)
    - created_at
    - updated_at
    - message_id (string)

16. **albums**
    - id
    - show_id (FK → shows.id)
    - image (string)
    - created_at
    - updated_at

---

### **Migration Order Summary**
1. **Independent Tables:** `users`, `payment_methods`, `mailchimp_lists`, `donation_levels`, `shows`, `site_configs`
2. **Level 1 Dependencies:** `mailchimp_members`
3. **Level 2 Dependencies:** `patrons`
4. **Level 3 Dependencies:** `patron_payment_ids`, `donations`
5. **Level 4 Dependencies:** `donation_perks`
6. **Level 5 Dependencies:** `performances`
7. **Level 6 Dependencies:** `tickets`
8. **Other Related Tables:** `contacts`, `fxr_webhook_responses`, `albums`
