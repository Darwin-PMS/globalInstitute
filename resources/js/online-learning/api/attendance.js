import { apiClient } from './client.js';

export const attendanceApi = {
    async getClassAttendance(classId) {
        return apiClient.get(`/api/online-learning/classes/${classId}/attendance`);
    },

    async markAttendance(classId, records) {
        return apiClient.post(`/api/online-learning/classes/${classId}/attendance`, { attendance: records });
    }
};
