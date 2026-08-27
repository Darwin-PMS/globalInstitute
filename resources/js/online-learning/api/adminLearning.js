import { apiClient } from './client.js';
import { compactParams } from '../utils/urlState.js';

const BASE = '/api/online-learning/admin';

export const adminLearningApi = {
    summary() {
        return apiClient.get(`${BASE}/summary`, {}, { abortKey: 'adminSummary' });
    },

    attendanceLog(params = {}) {
        return apiClient.get(`${BASE}/attendance`, compactParams(params), { abortKey: 'adminAttendance' });
    },
};
