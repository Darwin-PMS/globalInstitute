/**
 * Core API Client Wrapper for Online Learning SPA
 */
import { showPageLoading, hidePageLoading } from '../../ui/loading.js';

class ApiClient {
    constructor() {
        this.csrfToken = window.OnlineLearningConfig?.csrfToken || '';
        this.baseUrl = window.OnlineLearningConfig?.baseUrl || '';
        this.pendingControllers = new Map();
    }

    /**
     * Perform HTTP fetch with default headers & error handling
     */
    async request(url, options = {}) {
        const fullUrl = url.startsWith('http') ? url : `${this.baseUrl}${url}`;
        
        // Abort previous duplicate request key if provided
        if (options.abortKey) {
            if (this.pendingControllers.has(options.abortKey)) {
                this.pendingControllers.get(options.abortKey).abort();
            }
            const controller = new AbortController();
            options.signal = controller.signal;
            this.pendingControllers.set(options.abortKey, controller);
        }

        const headers = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.headers || {})
        };

        // Don't set Content-Type if sending FormData
        if (options.body && !(options.body instanceof FormData) && !headers['Content-Type']) {
            headers['Content-Type'] = 'application/json';
            if (typeof options.body === 'object') {
                options.body = JSON.stringify(options.body);
            }
        }

        // Every request reports itself on the shared progress bar, so no caller
        // has to remember to show navigation feedback.
        showPageLoading();

        try {
            const response = await fetch(fullUrl, { ...options, headers });
            
            // Clean controller key
            if (options.abortKey) {
                this.pendingControllers.delete(options.abortKey);
            }

            // Handle Unauthorized 401
            if (response.status === 401) {
                this.handleSessionExpired();
                throw new Error('Session expired. Please log in again.');
            }

            // Handle Forbidden 403
            if (response.status === 403) {
                const data = await response.json().catch(() => ({}));
                const error = new Error(data.message || 'You do not have permission to perform this action.');
                error.status = 403;
                error.errors = data.errors || {};
                throw error;
            }

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const error = new Error(data.message || `Request failed with status ${response.status}`);
                error.status = response.status;
                error.errors = data.errors || {};
                // Scheduling endpoints return a `conflicts` array alongside a 422;
                // callers render each clash, so keep it on the error.
                if (data.conflicts) error.conflicts = data.conflicts;
                error.payload = data;
                throw error;
            }

            return data;
        } catch (err) {
            if (err.name === 'AbortError') {
                return null;
            }
            throw err;
        } finally {
            hidePageLoading();
        }
    }

    get(url, params = {}, options = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;
        return this.request(fullUrl, { method: 'GET', ...options });
    }

    post(url, body = {}, options = {}) {
        return this.request(url, { method: 'POST', body, ...options });
    }

    put(url, body = {}, options = {}) {
        return this.request(url, { method: 'PUT', body, ...options });
    }

    delete(url, options = {}) {
        return this.request(url, { method: 'DELETE', ...options });
    }

    handleSessionExpired() {
        if (window.toast) {
            window.toast.error('Session expired. Redirecting to login...', 'Authentication Required');
        } else {
            alert('Your session has expired. Please login again.');
        }
        setTimeout(() => {
            window.location.href = '/login';
        }, 1500);
    }
}

export const apiClient = new ApiClient();
