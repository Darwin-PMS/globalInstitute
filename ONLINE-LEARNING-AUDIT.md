# Online Learning Module — SPA Audit, Fixes & Implementation Record

Audited and implemented 2026-08-22. Stack confirmed: Laravel 11 + Blade shell +
vanilla ES6 + Vite + MySQL. No framework migration. Blade remains the shell.

---

## 1. What already existed

The SPA foundation was already in place from prior work. This document records
what was working, what was broken, and what was added — it was never a proposal
to rewrite from scratch.

### Application shell
| Artifact | State |
|---|---|
| `resources/views/layouts/online-learning.blade.php` | Mounts `#learning-sidebar`, `#learning-topbar`, `#learning-content`, overlay + toast containers. Bootstraps `window.OnlineLearningConfig`. |
| `SpaController::onlineLearningShell` | Serves the shell for all three role prefixes. |
| Vite entry `resources/js/online-learning/app.js` | Registered in `vite.config.js`. |

### Deep-link routes
All three prefixes are catch-all, so direct URL access and F5 both work:
`/admin/online-learning/{any?}` (auth + admin), `/teacher/online-learning/{any?}`,
`/student/learning/{any?}`.

### Domain services (reused, not duplicated)
`ConflictDetectionService`, `AttendanceService`, `JoinAuthorizationService`,
`ProgressService`, `NotificationService`, `RecurrenceService` under
`app/Domains/OnlineLearning/Services/`.

### JS modules present before this work (2,430 lines)
- `api/`: `client.js` (CSRF, AbortController de-duplication, 401/403 handling), `classes.js`, `courses.js`, `attendance.js`
- `components/`: Sidebar, Topbar, ClassCard, ClassTable, ClassCalendar, Countdown, AttendanceTable, Modal, Drawer, Toast, EmptyState, LoadingState, ConfirmDialog
- `pages/`: admin Dashboard + Classes, teacher Dashboard, student Dashboard + Courses + Classes
- `utils/`: date, debounce, formatter

---

## 2. Defects found — all fixed

Every item below was reproduced before being changed. Ten were found by reading
the code; six more were found by the feature tests written for this work.

### Broken behaviour in the SPA layer

| # | Location | Defect | Impact | Fix |
|---|---|---|---|---|
| D1 | `pages/admin/ClassesPage.js` | `openEditDrawer()` / `openRescheduleDrawer()` called but never defined | `TypeError` — Edit and Reschedule were dead on every admin class row | Page rewritten with both drawers, a cancel drawer, and one shared create/edit form |
| D2 | `OnlineLearningApiController::rescheduleClass` | Called `NotificationService::classRescheduled()`, which did not exist | HTTP 500 on every reschedule, *after* the row had already been written | Method added (`class_rescheduled` was already a valid notification type) |
| D3 | `markAttendance` validation | Allowed `excused`; the `class_attendance.status` enum does not | DB error whenever a teacher chose "Excused" | Validation narrowed to `present,late,absent,partial` |
| D4 | `components/AttendanceTable.js` | Offered `excused`, omitted `partial` | Same mismatch from the UI side | Options aligned to the column |
| D5 | `Admin\Api\OnlineClassApiController::index` | Returned the raw paginator, not the shared envelope | Front end could not read `meta`, so pagination was impossible | All actions now return `{success, message, data, meta}` |
| D6 | `router.js` | No routes for admin attendance/recordings/materials, teacher classes/submissions, student recordings/assignments/progress | The sidebar linked to all of them; clicking silently bounced to the dashboard | Real route table with `:id` params, page teardown, breadcrumbs and a 404 screen |
| D7 | `router.js` | `/calendar` aliased to the class list | Calendar nav landed on the wrong screen | Dedicated calendar page (month/week/day/agenda) |
| D8 | `layouts/online-learning.blade.php` | Fixed 256px sidebar, `overflow-hidden`, no toggle | Unusable below ~768px, against the mobile-first requirement for students | Off-canvas drawer with scrim, Escape-to-close, topbar toggle |
| D9 | `pages/admin/ClassesPage.js` | Filters held only in memory | Refresh or share lost every filter | `utils/urlState.js` mirrors filters, view mode and page into the query string |
| D10 | `pages/admin/ClassesPage.js` | `meta` discarded | Only page 1 was ever reachable | `components/Pagination.js`, wired on every list screen |

### Defects the tests uncovered

| # | Location | Defect | Impact |
|---|---|---|---|
| D11 | `trainers` table | No `user_id` column, yet controllers resolve the acting teacher with `Trainer::where('user_id', …)` | Unknown-column SQL error on **every** teacher endpoint |
| D12 | `User` model | No `trainer()` / `student()` relation, so `$user->trainer` in the shell silently returned null | Teachers were served the **student** portal |
| D13 | `JoinAuthorizationService` | Built the join window by concatenating the class date onto `start_time`, which is a Carbon cast | `Carbon::parse('2026-08-22 2026-08-22 10:00:00')` threw — a 500 on the student join path, the module's most important action |
| D14 | `AttendanceService::markBulkAttendance` | Never resolved `enrollment_id`, which is `NOT NULL` | 500 on every attendance save from the roster UI |
| D15 | `rescheduleClass` / `cancelClass` | Passed **student** ids to a notifier that writes them into `lms_notifications.user_id` (a foreign key to `users`) | Notifications addressed to whichever user happened to share that id |
| D16 | `InstituteConflictDetection` | Overlap tested with `whereBetween` on each endpoint | Back-to-back sessions (10:00–12:00 then 12:00–14:00) were rejected as clashes |

Fixes for D13 and D16 were made in the shared domain services, so the existing
Blade screens get the same corrections.

### Security issues found along the way

| # | Finding | Fix |
|---|---|---|
| S1 | `users.role` was created with a default of **`'admin'`**, and `AdminMiddleware` gated on `role === 'admin'` — so any newly created account, including student and teacher logins, would have passed the admin gate | `User::isAdministrator()` is now the single predicate and reads the authoritative `is_admin` flag. A migration changes the column default to `'student'` and reconciles existing rows. Live data was checked first: no account was actually exposed. |
| S2 | `AdminMiddleware` called `Auth::logout()` when refusing a non-admin | Removed. In an SPA, one stray request destroyed the whole session. It now refuses with 403 (JSON) or a redirect (navigation) and leaves the session intact. |
| S3 | Class detail returned `meeting_url` to any viewer | Students never receive the link in the detail payload. It is issued only by `POST /join`, after enrollment and the join window are checked server-side, and the attempt is recorded. |

---

## 3. What was built

### Backend — modular, role-scoped, one envelope
```
app/Http/Controllers/Api/OnlineLearning/
├── Concerns/ApiResponses.php            {success, message, data, meta} + paginated() + clamped perPage
├── Concerns/ResolvesLearningActor.php   re-derives the actor from the session, never from input
├── ClassApiController.php               role-aware class detail + calendar range
├── StudentLearningApiController.php     recordings, assignments, submit, materials, progress
├── TeacherLearningApiController.php     submissions queue, grading, complete class
└── AdminLearningApiController.php       summary, attendance log, recordings, materials, publishing
```

`routes/online-learning.php` holds all 26 endpoints plus the three shell routes;
`routes/web.php` simply requires it.

### Frontend — nine new screens, no file over ~600 lines
```
resources/js/online-learning/
├── router.js                    route table, :id params, teardown, breadcrumbs, 404
├── api/          + learning, recordings, materials, assignments, progress, adminLearning
├── components/   + Pagination, ProgressBar
├── utils/        + urlState (query-string state), permissions (presentation-only roles)
└── pages/
    ├── admin/    Dashboard*, Classes*, Attendance, Recordings, Materials
    ├── teacher/  Dashboard, Classes, Submissions
    ├── student/  Dashboard*, Courses, Classes, ClassDetail, Recordings, Assignments, Progress
    └── shared/   Calendar, StaffClassDetail
```
`*` = rewritten.

Notable screens:
- **Student live class** (§26) — the flagship. Countdown, a banner that changes with
  the class lifecycle, a gated Join button, then recording / materials / assignment /
  attendance once it is over.
- **Calendar** (§21) — month, week, day and agenda. Drag-to-reschedule is deliberately
  absent: rescheduling has to run server-side conflict checks, so it stays an explicit
  action rather than a drag the front end could fake.
- **Teacher screens** (§23) — Join / Attendance / Details, and nothing else competing.
- **Admin dashboard** — summary tiles now come from database counts rather than from
  whatever happened to be on the first page of rows.

Cross-cutting: every list has loading skeleton, empty, error-with-retry and
paginated states; filters and view mode live in the URL; search is debounced and
de-duplicated through `AbortController`; forms show inline field errors from
Laravel and warn before discarding unsaved changes.

### Tests — 52 feature tests, 179 assertions, all passing
`tests/Feature/OnlineLearning/` covers class access and payload shaping, the join
path, student screens, staff operations, deep linking and scheduling. Six are
explicit regression guards for D2, D3, D13, D14, D15 and D16.

`phpunit.xml` now pins `APP_URL=http://localhost`. The `.env` value points at the
XAMPP sub-directory, which the test client was prepending to every request path —
so every request fell through to the SPA catch-all and returned the static shell.

---

## 4. Preserved, not replaced

Per §49 nothing was deleted. The legacy Blade dashboards under `/student` and
`/teacher` remain as the server-rendered fallback, as do the admin Blade CRUD
screens. Existing student, course, teacher, batch and enrollment relationships are
untouched apart from the additive `trainers.user_id` column.

### Verified against the running app
Logged in and exercised the shell plus every admin endpoint: role resolves to
`admin`, all responses use the envelope, MySQL stores clean `DATE`/`TIME` values, an
overlapping class is refused with a readable message, and a back-to-back class is
accepted. The two sample classes created during that check were removed afterwards.

---

## 5. Still open

- **Global search (§35)** is not built. The topbar has no search field; each list
  screen has its own debounced search instead.
- **Real-time (§44)** is not implemented, as instructed. Components re-read from the
  server after mutations, so a future WebSocket layer can push into the same paths.
- **Focus traps (§40)** — modals and drawers close on Escape, and the shell has a skip
  link plus ARIA labelling, but focus is not yet trapped inside an open overlay.
- **`Recording::course()`** uses a `hasOneThrough` whose key order looks wrong. Nothing
  in this work relies on it, so it was left alone — worth checking before anything does.
- **Teacher logins need linking.** `trainers.user_id` is new and was backfilled only
  where a trainer's email already matched a user account. Any trainer without a match
  still has a null `user_id` and will be treated as a student until an admin links them.
