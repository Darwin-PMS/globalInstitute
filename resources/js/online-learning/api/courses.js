import { apiClient } from './client.js';

export const coursesApi = {
    async getStudentDashboard() {
        return apiClient.get('/api/online-learning/student/dashboard');
    },

    async getStudentCourseDetail(courseId) {
        return apiClient.get(`/api/online-learning/student/courses/${courseId}`);
    },

    async getTeacherDashboard() {
        return apiClient.get('/api/online-learning/teacher/dashboard');
    }
};
