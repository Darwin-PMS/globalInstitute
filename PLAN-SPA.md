# Admin SPA Conversion Plan

## Architecture
- Entry: `public/admin_spa.html` (admin shell with sidebar)
- JS: `public/js/admin.js` (hash router + API calls + render)
- CSS: Uses existing `resources/css/admin.css` (already has design tokens)
- API: New JSON routes under `/api/admin/*`
- Auth: Session-based with CSRF token (same as frontend SPA)

## Phase A: Admin API Routes + Controllers
Create JSON API endpoints for all admin CRUD operations.

## Phase B: Admin SPA HTML + JS
Create the SPA shell, router, and page renderers.

## Phase C: Admin Login SPA
SPA-based login flow.

---

## Checklist

### Phase A: API Routes + Controllers
- [ ] A1: Create `routes/admin-api.php` with all admin API routes
- [ ] A2: Create `Admin\DashboardApiController` (stats, recent data)
- [ ] A3: Create `Admin\CourseApiController` (CRUD)
- [ ] A4: Create `Admin\CategoryApiController` (CRUD)
- [ ] A5: Create `Admin\TeacherApiController` (CRUD)
- [ ] A6: Create `Admin\StudentApiController` (CRUD)
- [ ] A7: Create `Admin\BatchApiController` (CRUD)
- [ ] A8: Create `Admin\EnquiryApiController` (CRUD + status)
- [ ] A9: Create `Admin\GalleryApiController` (CRUD + bulk + toggle)
- [ ] A10: Create `Admin\LessonApiController` (CRUD)
- [ ] A11: Create `Admin\OnlineClassApiController` (CRUD + conflict)
- [ ] A12: Create `Admin\MaterialApiController` (CRUD + file upload)
- [ ] A13: Create `Admin\AssignmentApiController` (CRUD)
- [ ] A14: Create `Admin\QuizApiController` (CRUD + questions)
- [ ] A15: Create `Admin\RecordingApiController` (CRUD)
- [ ] A16: Create `Admin\ProgressApiController` (read + stats)
- [ ] A17: Create `Admin\AuthApiController` (login/logout/whoami)
- [ ] A18: Register admin-api routes in `routes/web.php`

### Phase B: Admin SPA
- [ ] B1: Create `public/admin_spa.html` (shell with sidebar, header, content area)
- [ ] B2: Create `public/js/admin.js` (Router, API, Pages, Components)
- [ ] B3: Create `AdminSPAController` to serve the SPA HTML

### Phase C: Login
- [ ] C1: Login page in SPA
- [ ] C2: Auth state management (check session on load)
- [ ] C3: Logout flow

## Execution Order
1. Phase A (API endpoints) — foundation
2. Phase B (SPA shell + router) — UI
3. Phase C (Login) — auth
