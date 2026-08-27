import { apiClient } from './client.js';
import { compactParams } from '../utils/urlState.js';

const BASE = '/api/online-learning';

/**
 * Shared: single-class detail and the calendar range.
 * The server decides what each role is allowed to see in the payload.
 */
export const classDetailApi = {
    get(classId) {
        return apiClient.get(`${BASE}/classes/${classId}`, {}, { abortKey: `classDetail:${classId}` });
    },

    calendar(params = {}) {
        return apiClient.get(`${BASE}/calendar`, compactParams(params), { abortKey: 'calendar' });
    },
};

/**
 * Live Class Room entry.
 *
 * Resolves (and provisions on first use) the room for a scheduled class, then
 * the caller navigates to the room's own shell.
 */
export const roomApi = {
    resolveForClass(classId) {
        return apiClient.get(`${BASE}/classes/${classId}/room`);
    },
};
