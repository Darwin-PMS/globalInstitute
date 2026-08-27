import { apiClient } from './client.js';

export const classesApi = {
    // Admin classes list
    async getAdminClasses(params = {}) {
        return apiClient.get('/api/admin/online-classes', params, { abortKey: 'getClasses' });
    },

    // Get Admin class creation form data (courses, batches, teachers)
    async getFormData() {
        return apiClient.get('/api/admin/online-classes/form-data');
    },

    // Single class, role-aware (used by detail pages and ?edit= deep links)
    async getClass(id) {
        return apiClient.get(`/api/online-learning/classes/${id}`);
    },

    // Create new class
    async createClass(data) {
        return apiClient.post('/api/admin/online-classes', data);
    },

    // Update existing class
    async updateClass(id, data) {
        return apiClient.put(`/api/admin/online-classes/${id}`, data);
    },

    // Delete class
    async deleteClass(id) {
        return apiClient.delete(`/api/admin/online-classes/${id}`);
    },

    // Student classes list
    async getStudentClasses(params = {}) {
        return apiClient.get('/api/online-learning/student/classes', params, { abortKey: 'studentClasses' });
    },

    // Join online class
    async joinClass(classId) {
        return apiClient.post(`/api/online-learning/student/classes/${classId}/join`);
    },

    // Teacher classes list
    async getTeacherClasses(params = {}) {
        return apiClient.get('/api/online-learning/teacher/classes', params, { abortKey: 'teacherClasses' });
    },

    // Reschedule class
    async rescheduleClass(classId, data) {
        return apiClient.post(`/api/online-learning/classes/${classId}/reschedule`, data);
    },

    // Cancel class
    async cancelClass(classId, reason) {
        return apiClient.post(`/api/online-learning/classes/${classId}/cancel`, { reason });
    },

    // Teacher closes out a finished session
    async completeClass(classId) {
        return apiClient.post(`/api/online-learning/teacher/classes/${classId}/complete`);
    }
};
