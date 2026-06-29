# Performance Audit Summary

## 1. Missing Database Indexes — Highest Impact

Several frequently-queried columns have no indexes, so every lookup does a full table scan:

- `shows.slug` and `shows.poster` — queried in `ShowController::checkUniques()`
- `patrons.email` — queried in `TicketSaleController` and `PatronController`
- `comp_tickets.email` and `comp_tickets.show_id`
- `courses.slug`, `courses.enrollment_start`, `courses.enrollment_end`

On shared hosting where the DB server is already under resource pressure, missing indexes hurt the most.

---

## 2. Sequential API Calls on Page Load — High Impact

In `frontend/src/router/routes.js` around line 8–15, the root layout's `beforeEnter` hook `await`s five API calls one after another:

```js
await store.announcementBanner();
await store.seasonShows();
await store.homeShows();
await store.openCourses();
await store.flexshowPurchaseConfig();
```

These should all fire in parallel with `Promise.all()`. Right now every page load waits for all five to finish sequentially.

---

## 3. View Rendering Inside DB Loops — High Impact

Two places render Blade views per-record inside a collection loop:

- `app/Http/Controllers/ShowController.php` ~line 113–124: Calls `view()->render()` for every `StandardButton`
- `app/Http/Controllers/CourseController.php` ~line 23–28: The `getMessageAttribute()` accessor renders a Blade file per course, triggered inside `each()`

---

## 4. Individual DB Saves in Loops — High Impact for Those Operations

- `app/Http/Controllers/GalleryController.php` lines 118–124: Saves sort order one row at a time in a `foreach` — should use `upsert()`
- `app/Http/Controllers/VolunteerController.php` lines 112–117 and 154–159: Creates volunteer skills one at a time — should use bulk insert

---

## 5. Large Unoptimized Images — High Impact on Bandwidth

Raw uploads sitting in `storage/app/public/gallery-temp/` range from 577KB to 1.9MB per image. No resizing or WebP conversion in the upload pipeline. On a shared server, serving several of these on a single page is the most visible slow-down for end users.

---

## 6. Multiple Full Icon Libraries — Medium Impact

`frontend/quasar.config.js` lines 22–30 loads three full icon sets (`mdi-v7`, `fontawesome-v6`, `material-icons`) — that's thousands of icons bundled even if only dozens are used. Picking one set and sticking with it would trim bundle size noticeably.

---

## 7. No Query Caching for Static Data — Medium Impact

`SiteConfig::latest()->first()` is called on multiple requests without caching. Same for `StandardButton`, `AngelLevel`, `PermissionLevel` — these rarely change but are re-queried on every relevant page load. A `Cache::remember()` with a few minutes TTL would eliminate most of those hits.

---

## 8. Overly Large API Responses — Lower Impact

- `app/Http/Controllers/ContactController.php` line 64: `Contact::all()` — no column limiting, no pagination
- `app/Http/Controllers/UserController.php` line 27: Returns full users with all permission relations

---

## Priority Order

Quickest wins in order:
1. **Database indexes** — migration change only, no app logic change
2. **Parallelize `beforeEnter` API calls** — small frontend change, big visible impact
3. **Image optimization on upload** — add resizing/WebP conversion to the upload pipeline
