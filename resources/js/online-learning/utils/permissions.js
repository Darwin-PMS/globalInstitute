/**
 * Presentation-only role helpers.
 *
 * IMPORTANT: these decide what to *draw*, never what is *allowed* (§48).
 * Every endpoint re-derives the actor from the session, so hiding a button
 * here is a courtesy to the user, not a security control.
 */

export function config() {
    return window.OnlineLearningConfig || {};
}

export function role() {
    return config().role || 'student';
}

export const isAdmin = () => role() === 'admin';
export const isTeacher = () => role() === 'teacher';
export const isStudent = () => role() === 'student';

export function currentUser() {
    return config().user || {};
}

/**
 * The route prefix that owns the current role's screens.
 */
export function basePath() {
    if (isAdmin()) return '/admin/online-learning';
    if (isTeacher()) return '/teacher/online-learning';
    return '/student/learning';
}

/**
 * Build an in-module path, e.g. path('classes', 12) -> /student/learning/classes/12
 */
export function path(...segments) {
    const tail = segments.filter((s) => s !== null && s !== undefined && s !== '').join('/');
    return tail ? `${basePath()}/${tail}` : basePath();
}

/**
 * Coarse capability map used to decide which controls to render.
 */
export function can(action) {
    const capabilities = {
        admin: [
            'class.create', 'class.edit', 'class.delete', 'class.reschedule', 'class.cancel',
            'attendance.view', 'attendance.mark',
            'recording.manage', 'material.manage', 'calendar.view',
        ],
        teacher: [
            'class.view', 'class.complete', 'calendar.view',
            'attendance.view', 'attendance.mark',
            'submission.grade',
        ],
        student: [
            'class.view', 'class.join', 'calendar.view',
            'assignment.submit', 'recording.watch', 'material.download', 'progress.view',
        ],
    };

    return (capabilities[role()] || []).includes(action);
}
