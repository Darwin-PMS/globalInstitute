import { showPageLoading, hidePageLoading } from '../../../ui/loading.js';

/**
 * Room API client.
 *
 * Every call is scoped to one room key, so a panel cannot accidentally read
 * another room. De-duplicates in-flight GETs per key and aborts superseded ones,
 * which matters because panels and pollers overlap.
 */
export class RoomApi {
    constructor(roomKey, csrfToken, baseUrl = '') {
        this.base = `${baseUrl}/api/online-learning/rooms/${roomKey}`;
        this.liveClassesBase = `${baseUrl}/api/online-learning/live-classes`;
        this.csrfToken = csrfToken;
        this.controllers = new Map();
    }

    /**
     * @param {object}  opts
     * @param {boolean} opts.silent  skip the progress bar. Set on polled calls,
     *   which would otherwise keep the bar flashing every few seconds.
     */
    async request(path, { method = 'GET', body = null, abortKey = null, silent = false } = {}) {
        const url = `${this.base}${path}`;

        if (abortKey) {
            this.controllers.get(abortKey)?.abort();
        }

        if (!silent) showPageLoading();
        const controller = new AbortController();
        if (abortKey) this.controllers.set(abortKey, controller);

        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': this.csrfToken,
        };
        if (body) headers['Content-Type'] = 'application/json';

        try {
            const response = await fetch(url, {
                method,
                headers,
                credentials: 'same-origin',
                signal: controller.signal,
                body: body ? JSON.stringify(body) : null,
            });

            if (abortKey) this.controllers.delete(abortKey);

            const data = await response.json().catch(() => ({}));

            if (response.status === 401 || response.status === 419) {
                const error = new Error('Your session has expired.');
                error.status = response.status;
                error.sessionExpired = true;
                throw error;
            }

            if (!response.ok) {
                const error = new Error(data.message || `Request failed (${response.status})`);
                error.status = response.status;
                error.errors = data.errors || {};
                throw error;
            }

            return data;
        } catch (err) {
            // A superseded request is not a failure the caller should handle.
            if (err.name === 'AbortError') return null;
            throw err;
        } finally {
            if (!silent) hidePageLoading();
        }
    }

    /* shell + lifecycle */
    bootstrap() { return this.request('', { abortKey: 'bootstrap' }); }
    state() { return this.request('/state', { abortKey: 'state', silent: true }); }
    enter() { return this.request('/enter', { method: 'POST' }); }
    heartbeat() { return this.request('/heartbeat', { method: 'POST', silent: true }); }
    leave() { return this.request('/leave', { method: 'POST' }); }
    start() { return this.request('/start', { method: 'POST' }); }
    end() { return this.request('/end', { method: 'POST' }); }
    updateSettings(settings) { return this.request('/settings', { method: 'PUT', body: settings }); }

    /* panels — each fetched only when its tab is opened */
    materials() { return this.request('/materials', { abortKey: 'materials' }); }
    recordings() { return this.request('/recordings', { abortKey: 'recordings' }); }
    assignments() { return this.request('/assignments', { abortKey: 'assignments' }); }
    roster() { return this.request('/roster', { abortKey: 'roster' }); }
    presence() { return this.request('/presence', { abortKey: 'presence', silent: true }); }
    attendance() { return this.request('/attendance', { abortKey: 'attendance' }); }
    saveAttendance(records) { return this.request('/attendance', { method: 'POST', body: { records } }); }

    /* deltas — `after` makes these incremental */
    activity(after = null) {
        return this.request(`/activity${after ? `?after=${after}` : ''}`, {
            abortKey: 'activity',
            silent: after !== null, // a delta poll should not flash the bar
        });
    }
    messages(after = null) {
        return this.request(`/messages${after ? `?after=${after}` : ''}`, {
            abortKey: `messages:${after ? 'delta' : 'initial'}`,
            silent: after !== null,
        });
    }
    postMessage(body, type = 'message') {
        return this.request('/messages', { method: 'POST', body: { body, type } });
    }
    pinMessage(id, pinned = true) {
        return this.request(`/messages/${id}/pin`, { method: 'POST', body: { pinned } });
    }
    deleteMessage(id) { return this.request(`/messages/${id}`, { method: 'DELETE' }); }

    /** Drop any in-flight requests — called when the room is torn down. */
    abortAll() {
        this.controllers.forEach((c) => c.abort());
        this.controllers.clear();
    }
}
