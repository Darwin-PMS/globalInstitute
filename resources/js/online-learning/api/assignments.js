import { apiClient } from './client.js';
import { compactParams } from '../utils/urlState.js';

const BASE = '/api/online-learning';

export const assignmentsApi = {
    listForStudent(params = {}) {
        return apiClient.get(`${BASE}/student/assignments`, compactParams(params), { abortKey: 'studentAssignments' });
    },

    /**
     * FormData is sent as-is so the file part survives; apiClient skips
     * Content-Type for FormData bodies.
     */
    submit(assignmentId, formData) {
        return apiClient.post(`${BASE}/student/assignments/${assignmentId}/submit`, formData);
    },

    listSubmissions(params = {}) {
        return apiClient.get(`${BASE}/teacher/submissions`, compactParams(params), { abortKey: 'teacherSubmissions' });
    },

    evaluate(submissionId, payload) {
        return apiClient.post(`${BASE}/teacher/submissions/${submissionId}/evaluate`, payload);
    },
};
