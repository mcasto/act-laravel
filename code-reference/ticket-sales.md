# Ticket Sales — Non-Fixr Purchase Flow

This documents the architecture for manual payment methods (PayPal, bank transfer, flex packages). Fixr ticket sales are handled entirely via webhook and are separate.

---

## Overview

The user selects a performance and a payment method on the purchase page. For non-Fixr methods, a blade partial is rendered server-side and injected into the Vue component as raw HTML. The patron fills out a form in that partial and submits it to the API.

---

## Data Flow

```
PurchaseTickets.vue (page)
  └── PurchaseOptions.vue (component)
        └── v-html → blade partial (paypal | transfer | flex)
              └── POST /api/ticket-sales → TicketSaleController@store
```

---

## Files

| File | Role |
|------|------|
| `frontend/src/pages/PurchaseTickets.vue` | Page — holds `performance` and `paymentMethod` state, renders the performance select |
| `frontend/src/components/PurchaseOptions.vue` | Component — holds the payment method select, injects the blade partial via `v-html` |
| `app/Http/Controllers/StandardButtonsController.php` | Fetches `StandardButton` records, renders each blade partial server-side, returns `popupText` HTML |
| `resources/views/standard-buttons/paypal.blade.php` | Blade partial for PayPal payments |
| `resources/views/standard-buttons/transfer.blade.php` | Blade partial for bank transfer payments |
| `resources/views/standard-buttons/flex.blade.php` | Blade partial for flex package redemption |
| `app/Http/Controllers/PatronController.php` | Patron email lookup endpoint — returns name & phone for pre-fill |
| `app/Http/Controllers/TicketSaleController.php` | Receives the form POST and stores the ticket sale |
| `app/Models/StandardButton.php` | DB-driven record; `key` column maps to the blade filename |

---

## StandardButton Records

`StandardButton` rows are stored in the database. The `key` column maps directly to the blade filename:

| key | Blade partial |
|-----|---------------|
| `paypal` | `standard-buttons/paypal.blade.php` |
| `transfer` | `standard-buttons/transfer.blade.php` |
| `flex` | `standard-buttons/flex.blade.php` |

To add a new payment method, add a row to the `standard_buttons` table and create a matching blade partial. No code changes required.

`StandardButtonsController@index` maps over all buttons and renders each partial server-side, passing `$price` (the ticket amount). The rendered HTML is returned as `popupText` on each button object.

---

## Blade Partials

Each partial is a self-contained HTML form that posts to `POST /api/ticket-sales`. They share the same structure:

- A `<fieldset>` with a numbered legend ("2. Submit this form") to visually group the form as step 2 of the payment flow
- Dense, outlined `q-field`-styled inputs (plain CSS mimicking Quasar's `outlined` + `stack-label` + `dense` appearance — not actual Vue components)
- Required fields: `email`, `name`, `phone`
- Hidden fields:
  - `type` — hardcoded to the payment method name (`paypal`, `transfer`, `flex`)
  - `performance_id` — left blank on render, populated by Vue after mount (see below)
- The `flex` partial additionally has a `seats` (number) field

### Email Patron Lookup

All three partials fire a vanilla JS `fetch` on the email field's `blur` event:

```
GET /api/patrons/lookup?email={email}
→ PatronController@lookup
→ 200 { name, phone } or 404
```

On a 200 response, the `name` and `phone` fields are pre-filled. This avoids asking returning patrons to re-enter their details.

---

## Injecting `performance_id` from Vue

Because the partials are rendered server-side and injected as static HTML via `v-html`, Vue cannot bind to their inputs directly. Instead, `PurchaseOptions.vue` uses a watcher:

```js
watch([paymentMethod, () => props.performance], async () => {
  await nextTick(); // wait for v-html to finish rendering
  const field = document.querySelector('[name="performance_id"]');
  if (field && props.performance) {
    field.value = props.performance.id;
  }
});
```

The watcher fires on:
- **Payment method change** — when `v-html` re-renders with a new partial, the new `performance_id` field needs to be populated
- **Performance change** — when the user picks a different performance date, the existing field value needs updating

`performance` is passed down as a prop: `PurchaseTickets` → `PurchaseOptions`.

---

## Adding or Modifying a Partial

1. Create `resources/views/standard-buttons/{key}.blade.php`
2. Include hidden fields `type` (hardcoded) and `performance_id` (blank)
3. Use `id` attributes prefixed with the key (e.g. `flex-email`) to avoid conflicts if multiple partials are ever on the same page simultaneously
4. Add a row to `standard_buttons` table with the matching `key`
5. No changes needed to `StandardButtonsController`, `PurchaseOptions.vue`, or `PurchaseTickets.vue`
