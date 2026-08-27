
    /* ---------- PROGRESS ---------- */
    progress: {
        title: 'Progress',
        api: '/progress',
        route: 'progress',
        readOnly: true,
        columns: [
            { key: 'id', label: 'ID' },
            { key: 'student', label: 'Student', render: function(v) { return v ? escapeHtml(v.name) : '-'; } },
            { key: 'lesson', label: 'Lesson', render: function(v) { return v ? escapeHtml(v.title) : '-'; } },
            { key: 'status', label: 'Status', render: function(v) { return statusBadge(v); } },
            { key: 'progress_percentage', label: 'Progress', render: function(v) { return v != null ? '<div class="progress" style="height:20px;width:120px;"><div class="progress-bar" role="progressbar" style="width:' + v + '%;">' + v + '%</div></div>' : '-'; } },
            { key: 'completed_at', label: 'Completed At', render: function(v) { return v ? new Date(v).toLocaleString() : '-'; } }
        ],
        actions: function(row) {
            return '<span class="text-muted">Read only</span>';
        }
    }
};

/* ----------------------------------------------------------
   8. Dashboard
   ---------------------------------------------------------- */
function DashboardPage() {
    var content = document.getElementById('admin-content');
    AdminApp.setTitle('Dashboard');
    content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';

    Api.get('/dashboard').then(function(data) {
        var stats = data.data || data;
        var totalCourses = stats.total_courses || 0;
        var activeClasses = stats.active_classes || 0;
        var totalStudents = stats.total_students || 0;
        var totalEnquiries = stats.total_enquiries || 0;
        var recentEnquiries = stats.recent_enquiries || [];
        var recentCourses = stats.recent_courses || [];

        var html = '<div class="d-flex justify-content-between align-items-center mb-4">' +
            '<h4 class="mb-0">Dashboard</h4>' +
            '</div>';

        html += '<div class="row mb-4">' +
            '<div class="col-md-3 mb-3">' +
            '<div class="card shadow-sm border-0" style="background:linear-gradient(135deg,#667eea,#764ba2);">' +
            '<div class="card-body text-white">' +
            '<div class="d-flex justify-content-between align-items-center">' +
            '<div><h6 class="mb-1 opacity-75">Total Courses</h6><h2 class="mb-0 fw-bold">' + escapeHtml(String(totalCourses)) + '</h2></div>' +
            '<i class="bi bi-book fs-1 opacity-50"></i>' +
            '</div></div></div></div>' +
            '<div class="col-md-3 mb-3">' +
            '<div class="card shadow-sm border-0" style="background:linear-gradient(135deg,#f093fb,#f5576c);">' +
            '<div class="card-body text-white">' +
            '<div class="d-flex justify-content-between align-items-center">' +
            '<div><h6 class="mb-1 opacity-75">Active Classes</h6><h2 class="mb-0 fw-bold">' + escapeHtml(String(activeClasses)) + '</h2></div>' +
            '<i class="bi bi-camera-video fs-1 opacity-50"></i>' +
            '</div></div></div></div>' +
            '<div class="col-md-3 mb-3">' +
            '<div class="card shadow-sm border-0" style="background:linear-gradient(135deg,#4facfe,#00f2fe);">' +
            '<div class="card-body text-white">' +
            '<div class="d-flex justify-content-between align-items-center">' +
            '<div><h6 class="mb-1 opacity-75">Total Students</h6><h2 class="mb-0 fw-bold">' + escapeHtml(String(totalStudents)) + '</h2></div>' +
            '<i class="bi bi-mortarboard fs-1 opacity-50"></i>' +
            '</div></div></div></div>' +
            '<div class="col-md-3 mb-3">' +
            '<div class="card shadow-sm border-0" style="background:linear-gradient(135deg,#fa709a,#fee140);">' +
            '<div class="card-body text-white">' +
            '<div class="d-flex justify-content-between align-items-center">' +
            '<div><h6 class="mb-1 opacity-75">Total Enquiries</h6><h2 class="mb-0 fw-bold">' + escapeHtml(String(totalEnquiries)) + '</h2></div>' +
            '<i class="bi bi-envelope fs-1 opacity-50"></i>' +
            '</div></div></div></div>' +
            '</div>';

        if (recentEnquiries.length) {
            html += '<div class="card shadow-sm mb-4"><div class="card-header bg-white"><h5 class="mb-0">Recent Enquiries</h5></div>' +
                '<div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle">' +
                '<thead class="table-dark"><tr><th>ID</th><th>Name</th><th>Email</th><th>Course</th><th>Status</th><th>Date</th></tr></thead><tbody>';
            for (var i = 0; i < recentEnquiries.length; i++) {
                var eq = recentEnquiries[i];
                html += '<tr><td>' + eq.id + '</td><td>' + escapeHtml(eq.name) + '</td><td>' + escapeHtml(eq.email) + '</td>' +
                    '<td>' + (eq.course ? escapeHtml(eq.course.title) : '-') + '</td>' +
                    '<td>' + statusBadge(eq.status) + '</td>' +
                    '<td>' + (eq.created_at ? new Date(eq.created_at).toLocaleDateString() : '-') + '</td></tr>';
            }
            html += '</tbody></table></div></div></div>';
        }

        if (recentCourses.length) {
            html += '<div class="card shadow-sm mb-4"><div class="card-header bg-white"><h5 class="mb-0">Recent Courses</h5></div>' +
                '<div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle">' +
                '<thead class="table-dark"><tr><th>ID</th><th>Title</th><th>Category</th><th>Price</th><th>Status</th><th>Created</th></tr></thead><tbody>';
            for (var j = 0; j < recentCourses.length; j++) {
                var co = recentCourses[j];
                html += '<tr><td>' + co.id + '</td><td>' + escapeHtml(co.title) + '</td>' +
                    '<td>' + (co.category ? escapeHtml(co.category.name) : '-') + '</td>' +
                    '<td>' + (co.price != null ? '₹' + escapeHtml(String(co.price)) : '-') + '</td>' +
                    '<td>' + statusBadge(co.status) + '</td>' +
                    '<td>' + (co.created_at ? new Date(co.created_at).toLocaleDateString() : '-') + '</td></tr>';
            }
            html += '</tbody></table></div></div></div>';
        }

        content.innerHTML = html;
    }).catch(function(err) {
        content.innerHTML = '<div class="alert alert-danger">Error loading dashboard: ' + escapeHtml(err.message || 'Unknown error') + '</div>';
    });
}
