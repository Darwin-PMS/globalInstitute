/**
 * Date & Time Utilities for LMS SPA
 */

export function formatDate(dateString, options = {}) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;

    const defaultOptions = {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    };

    return new Intl.DateTimeFormat('en-US', { ...defaultOptions, ...options }).format(date);
}

export function formatTime(timeString) {
    if (!timeString) return 'N/A';
    if (timeString.includes('T')) {
        const d = new Date(timeString);
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    const [hours, minutes] = timeString.split(':');
    const h = parseInt(hours, 10);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const formattedHours = h % 12 || 12;
    return `${formattedHours}:${minutes} ${ampm}`;
}

export function getCountdownParts(targetDateStr, targetTimeStr) {
    if (!targetDateStr) return null;
    let target;

    if (targetTimeStr) {
        target = new Date(`${targetDateStr}T${targetTimeStr}`);
    } else {
        target = new Date(targetDateStr);
    }

    const now = new Date();
    const diffMs = target.getTime() - now.getTime();

    if (diffMs <= 0) {
        return { totalMs: diffMs, days: 0, hours: 0, minutes: 0, seconds: 0, isPast: true };
    }

    const seconds = Math.floor((diffMs / 1000) % 60);
    const minutes = Math.floor((diffMs / 1000 / 60) % 60);
    const hours = Math.floor((diffMs / (1000 * 60 * 60)) % 24);
    const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    return { totalMs: diffMs, days, hours, minutes, seconds, isPast: false };
}

/**
 * Time-of-day greeting for the student hero banner.
 */
export function greeting(date = new Date()) {
    const hour = date.getHours();

    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
}
