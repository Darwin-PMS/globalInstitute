import { apiClient } from './client.js';

const BASE = '/api/online-learning';

export const progressApi = {
    forStudent() {
        return apiClient.get(`${BASE}/student/progress`, {}, { abortKey: 'studentProgress' });
    },
};
