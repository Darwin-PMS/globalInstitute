import { apiClient } from './client.js';
import { compactParams } from '../utils/urlState.js';

const BASE = '/api/online-learning';

export const recordingsApi = {
    listForStudent(params = {}) {
        return apiClient.get(`${BASE}/student/recordings`, compactParams(params), { abortKey: 'studentRecordings' });
    },

    listForAdmin(params = {}) {
        return apiClient.get(`${BASE}/admin/recordings`, compactParams(params), { abortKey: 'adminRecordings' });
    },

    togglePublish(recordingId) {
        return apiClient.post(`${BASE}/admin/recordings/${recordingId}/toggle-publish`);
    },
};
