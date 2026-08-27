import { apiClient } from './client.js';
import { compactParams } from '../utils/urlState.js';

const BASE = '/api/online-learning';

export const materialsApi = {
    listForStudent(params = {}) {
        return apiClient.get(`${BASE}/student/materials`, compactParams(params), { abortKey: 'studentMaterials' });
    },

    listForAdmin(params = {}) {
        return apiClient.get(`${BASE}/admin/materials`, compactParams(params), { abortKey: 'adminMaterials' });
    },

    togglePublish(materialId) {
        return apiClient.post(`${BASE}/admin/materials/${materialId}/toggle-publish`);
    },
};
