I need the following:
    migration for `patrons` table
        last_name (str)
        first_name (str)
        phone (str)
        email (text)
        flex (?)

    migration to modify `ticket_sales` table
        remove:
            first_name
            last_name
            email
            mobile_number
            contact_preferences_user_response
        add foreign key for `patrons` table

---

For the `flex(?)` column ... I'm not sure how to handle that.

The situation is that, at the start of a season, we sell "flex" packages--6 tickets for the price of 5. So, we need to track how many flex tix a patron has remaining for this season *and* be able to pull the record of flex tix they used this season.

What do you recommend?
