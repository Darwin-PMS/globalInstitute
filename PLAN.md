# Online Learning Module — Master Plan

## Current State
- 15 PHP Enums ✅
- 10 Contract Interfaces ✅
- Config file ✅
- 14 database migrations (LMS tables) ✅
- 14 Eloquent models ✅
- 7 Admin controllers ✅
- 2 Frontend controllers ✅
- 33 Blade views ✅
- Routes (admin + student + teacher) ✅

---

## Phase A: Fix All 11 Bugs

| # | File | Bug | Fix |
|---|------|-----|-----|
| A1 | `StudentDashboardController.php:71` | `Recording::whereIn('course_id', ...)` — column doesn't exist | Query through `onlineClass` relationship |
| A2 | `QuizAnswer.php:10,14` | Fillable/casts use `is_correct`, DB column is `is_passed` | Change to `is_passed` |
| A3 | `TeacherDashboardController.php:66` | `->where('is_graded', false)` — column doesn't exist | Use `->whereNull('score')` |
| A4 | `TeacherDashboardController.php:111,122` | Validates/writes `remarks` — DB column is `notes` | Change to `notes` |
| A5 | `TeacherDashboardController.php:124` | Writes `marked_at` — column doesn't exist | Remove from array |
| A6 | `TeacherDashboardController.php:129` | Updates `is_attendance_taken` on OnlineClass — column doesn't exist | Remove |
| A7 | `TeacherDashboardController.php:152` | Validates `marks_obtained` — DB column is `marks` | Change to `marks` |
| A8 | `TeacherDashboardController.php:153` | Validates `feedback` — DB column is `teacher_feedback` | Change to `teacher_feedback` |
| A9 | `TeacherDashboardController.php:156` | Sets status `evaluated` — not in enum | Change to `reviewed` |
| A10 | `StudentDashboardController.php:227` | Missing `enrollment_id` — column NOT nullable | Add enrollment lookup + `submitted_at` |
| A11 | `StudentDashboardController.php:206` | `Recording::whereIn('course_id', ...)` — same as A1 | Fix same way |

## Phase B: Database — New Tables

| # | Migration | Table | Purpose |
|---|-----------|-------|---------|
| B1 | `create_live_classes_table` | `live_classes` | Logical class entity (title, teacher, course, module, type, status) |
| B2 | `create_class_sessions_table` | `class_sessions` | Scheduled occurrences (date, time, meeting, status) |
| B3 | `create_class_messages_table` | `class_messages` | Announcements + chat per session |
| B4 | `fix_quiz_answers_column` | `quiz_answers` | Rename `is_passed` → `is_correct` |
| B5 | `add_timezone_to_online_classes` | `online_classes` | Add `timezone` varchar column |
| B6 | `add_tenant_and_type_to_online_classes` | `online_classes` | Add `class_type`, `access_policy`, `tenant_id` |

## Phase C: Model Refactoring

| # | Model | Change |
|---|-------|--------|
| C1 | `Course` | Add `lessons()`, `onlineClasses()`, `quizzes()`, `assignments()`, `materials()`, `recordings()` |
| C2 | `CourseModule` | Add `lessons()` |
| C3 | `Recording` | Add `course()` via `onlineClass` |
| C4 | `LearningMaterial` | Add `class()` alias for `class_id` FK |
| C5 | Multiple | Add `SoftDeletes` to: LiveClass, CourseModule, Lesson, Assignment, LearningMaterial, Recording, Quiz |
| C6 | Multiple | Add `tenant_id` (nullable) to major LMS models |
| C7 | New | Create `LiveClass` model |
| C8 | New | Create `ClassSession` model |
| C9 | New | Create `ClassMessage` model |
| C10 | `OnlineClass` | Use Enums from `App\Enums\*` instead of inline strings |

## Phase D: Service Layer

| # | Service | Purpose |
|---|---------|---------|
| D1 | `ProgressService` | Central progress calculation (lesson, module, course) |
| D2 | `AttendanceService` | Mark attendance, calculate status, get stats |
| D3 | `ConflictDetectionService` | Teacher/batch/room/schedule conflict checks |
| D4 | `RecurrenceService` | Generate recurring class sessions from pattern |
| D5 | `NotificationService` | Send LMS notifications (wraps contract) |
| D6 | `JoinAuthorizationService` | Validate student can join class (enrollment, time window, status) |

## Phase E: Institute Adapters

| # | Adapter | Implements |
|---|---------|------------|
| E1 | `InstituteStudentProvider` | `StudentProviderInterface` |
| E2 | `InstituteTeacherProvider` | `TeacherProviderInterface` |
| E3 | `InstituteCourseProvider` | `CourseProviderInterface` |
| E4 | `InstituteEnrollmentProvider` | `EnrollmentProviderInterface` |

## Phase F: Service Provider

| # | File | Purpose |
|---|------|---------|
| F1 | `OnlineLearningServiceProvider.php` | Register all bindings (contracts → adapters, services) |

## Phase G: Events & Jobs

| # | File | Purpose |
|---|------|---------|
| G1 | `ClassScheduled` event | Fired when class session created |
| G2 | `ClassCancelled` event | Fired when class cancelled |
| G3 | `RecordingAvailable` event | Fired when recording published |
| G4 | `AssignmentEvaluated` event | Fired when submission graded |
| G5 | `SendClassReminderJob` | Queued job for reminders |

## Phase H: Refactor Controllers

| # | Controller | Change |
|---|-----------|--------|
| H1 | `OnlineClassController` | Delegate to ConflictDetectionService, NotificationService |
| H2 | `TeacherDashboardController` | Use AttendanceService, fix all bugs |
| H3 | `StudentDashboardController` | Use ProgressService, JoinAuthorizationService |
| H4 | `RecordingController` | Use StorageProviderInterface |

## Phase I: Build & Verify

| # | Task |
|---|------|
| I1 | `php artisan migrate` |
| I2 | `npm run build` |
| I3 | `php artisan view:clear && php artisan view:cache` |
| I4 | `php artisan route:list` |
| I5 | Test all admin LMS routes in browser |
| I6 | Test student dashboard |
| I7 | Test teacher dashboard |

---

## Execution Order

1. Phase A (bugs) — CRITICAL, fixes runtime errors
2. Phase B (migrations) — new tables needed for architecture
3. Phase C (models) — relationships + new models
4. Phase D (services) — business logic layer
5. Phase E (adapters) — contract implementations
6. Phase F (service provider) — register bindings
7. Phase G (events/jobs) — async support
8. Phase H (controllers) — thin controllers
9. Phase I (build/verify) — final check
