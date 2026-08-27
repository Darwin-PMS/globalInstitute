/**
 * Skeletons for the Online Learning SPA.
 *
 * These now delegate to the shared templates in `resources/js/ui/loading.js`, so
 * the whole portal draws loading states from one vocabulary. The original
 * function names are kept because the existing pages import them.
 */
import { skeletons, showSkeleton, hideSkeleton, showLoader, hideLoader } from '../../ui/loading.js';

/* --------------------------------------------------- original API (kept) --- */

export function getDashboardSkeletonHTML() {
    return skeletons.dashboard();
}

export function getTableSkeletonHTML(rows = 5) {
    return skeletons.table({ rows, cols: 6 });
}

export function getCardsSkeletonHTML(count = 6) {
    return skeletons.cards({ count, media: false });
}

/* ------------------------------------------------------ module additions --- */

export function getListSkeletonHTML(rows = 5) {
    return skeletons.list({ rows });
}

export function getStatsSkeletonHTML(count = 4) {
    return skeletons.stats({ count });
}

export function getFormSkeletonHTML(fields = 6) {
    return skeletons.form({ fields });
}

export function getProfileSkeletonHTML() {
    return skeletons.profile();
}

export function getChartSkeletonHTML(bars = 8) {
    return skeletons.chart({ bars });
}

/** Month grid for the class calendar. */
export function getCalendarSkeletonHTML(weeks = 5) {
    return skeletons.calendar({ weeks });
}

/** Student/participant list with a status control per row. */
export function getRosterSkeletonHTML(rows = 6) {
    return skeletons.roster({ rows });
}

/** Attendance shares the roster shape — each row ends in a status control. */
export function getAttendanceSkeletonHTML(rows = 6) {
    return skeletons.roster({ rows });
}

export function getChatSkeletonHTML(rows = 5) {
    return skeletons.chat({ rows });
}

/** Materials, recordings and assignments are all card grids with a thumbnail. */
export function getResourceSkeletonHTML(count = 6) {
    return skeletons.cards({ count, media: true });
}

/** Inside a modal or drawer body. */
export function getModalSkeletonHTML(lines = 4) {
    return skeletons.modal({ lines });
}

/**
 * The class-detail screen: banner, then two columns of panels.
 * Mirrors StudentClassDetailPage / StaffClassDetailPage so the swap is stable.
 */
export function getClassDetailSkeletonHTML() {
    return `
        <div class="skeleton-group" style="gap:24px" aria-hidden="true">
            <div class="skeleton" style="height:200px;border-radius:16px"></div>
            <div class="skeleton-stats">
                ${Array(4).fill('<div class="skeleton" style="height:76px;border-radius:16px"></div>').join('')}
            </div>
            <div class="skeleton-grid" style="grid-template-columns:2fr 1fr">
                <div class="skeleton" style="height:260px;border-radius:16px"></div>
                <div class="skeleton" style="height:260px;border-radius:16px"></div>
            </div>
        </div>
    `;
}

/* ---------------------------------------------------------- re-exports ---- */

// So pages can drive skeletons and section loaders from this one import.
export { showSkeleton, hideSkeleton, showLoader, hideLoader, skeletons };
