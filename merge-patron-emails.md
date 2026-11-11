# Merging duplicate patrons (patron_emails)

Some patrons have more than one email address, which used to create a separate patron record per address. The new `patron_emails` table lets one patron have several addresses, and `patrons:merge-duplicates` combines the existing duplicate records.

## Run on production

After deploying:

```bash
php artisan migrate
php artisan patrons:merge-duplicates --dry-run   # review the plan
php artisan patrons:merge-duplicates             # shows the plan again, asks, then merges
```

Production may have changed since the last copy to dev, so read the dry-run list before confirming.

## How the command decides

- **Duplicates:** patrons with the same full name, ignoring case and extra spaces.
- **Record kept,** in order of preference:
  1. most ticket sales
  2. most other records (Angel, Flex, donations, classes, Fixr, …)
  3. has a real email address (not blank, not a name typed into the field, not a `no-email+…@address.com` stand-in)
  4. oldest record
- **Skipped:** the John Doe placeholders, plus any name passed with `--except="Name"` (repeatable).
- **Options:** `--dry-run` shows the plan only; `--force` merges without asking.

## What a merge does

The command and the Patrons page's Merge action use the same code (`App\Services\PatronMerger`).

- Everything pointing at the duplicate moves to the patron kept: ticket sales (deleted ones too, so history stays together), Angel records, Flex packages and pending Flex requests, donations, class enrollments, Fixr records, reminder records, payment records and email addresses.
- The duplicate's email addresses become extra addresses on the patron kept.
- Missing phone and comments are copied over. Front-row need and Founding Angel take whichever is set. Nothing on the patron kept is overwritten.
- If the kept patron's main address isn't real (blank, invalid or a `no-email+…` stand-in), a real address from the merged record becomes the main one.
- The duplicate is soft-deleted, not erased, and the merge is logged in the change log.

## Email addresses

- Every address a patron uses is in `patron_emails`, the main one included. Email goes to the main address (`patrons.email`).
- An address belongs to one patron only. Adding an address that another patron already has offers to merge the two instead of moving it.
- All patron lookups by email (ticket sales, Fixr, Angels, Flex, comps, classes, admin lookups) match any of a patron's addresses, so a purchase under a second address attaches to the same patron.
- Addresses are cleaned when read: lowercased and trimmed, with stray `<`, `>`, quotes and `mailto:` removed.
- Patrons page: an **N emails** button lists a patron's addresses. Edit Patron lets you add or remove addresses, choose the main one, and merge a duplicate in.

## Dev results (dry run, rolled back)

15 people, 16 duplicate records merged; patrons went from 277 to 261. A second run found nothing left to merge. Only the John Doe pair still shares a name. Jill Jordan was already merged on dev, so production will list 16 people.

## Check before confirming on production

- **Mike Casto:** #1 castoware@gmail.com and #2 agps.guru@gmail.com would be merged into #1. Add `--except="Mike Casto"` if they should stay separate.
- **Marcia Mansaray:** her real address, alphaandmarcia@gmail.com, is a shared household address that belongs to #152 Alpha Mansaray. A merge never takes an address from another patron, so Marcia keeps her `no-email+…` stand-in as her main address.
- **Bad values:** #35 Pete Fischer's email field holds a name rather than an address, so it isn't listed as an email.
