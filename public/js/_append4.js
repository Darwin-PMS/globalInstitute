
    /* ---------- ASSIGNMENTS ---------- */
    assignments: {
        title: 'Assignments',
        api: '/assignments',
        route: 'assignments',
        columns: [
            { key: 'id', label: 'ID' },
            { key: 'title', label: 'Title', render: function(v) { return escapeHtml(v); } },
            { key: 'course', label: 'Course', render: function(v) { return v ? escapeHtml(v.title) : '-'; } },
            { key: 'due_date', label: 'Due Date', render: function(v) { return v ? new Date(v).toLocaleString() : '-'; } },
            { key: 'max_marks', label: 'Max Marks', render: function(v) { return v != null ? String(v) : '-'; } },
            { key: 'status', label: 'Status', render: function(v) { return statusBadge(v); } }
        ],
        actions: function(row) {
            return '<a href="#/assignments/' + row.id + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="GenericDelete(pages.assignments,' + row.id + ')" title="Delete"><i class="bi bi-trash"></i></button>';
        },
        formFields: function(item) {
            var c = item || {};
            return selectField('course_id', 'Course', c.course_id, []) +
                inputField('title', 'Title', c.title) +
                textareaField('description', 'Description', c.description) +
                textareaField('instructions', 'Instructions', c.instructions) +
                inputField('due_date', 'Due Date', c.due_date ? c.due_date.substring(0, 16) : '', 'datetime-local') +
                inputField('max_marks', 'Max Marks', c.max_marks, 'number') +
                inputField('submission_types', 'Submission Types', c.submission_types) +
                statusSelect('status', 'Status', c.status);
        },
        formDataProvider: function() {
            return Api.get('/courses?per_page=100').then(function(res) {
                var courses = res.data || res || [];
                var cSel = document.querySelector('[name="course_id"]');
                if (cSel) {
                    for (var i = 0; i < courses.length; i++) {
                        var opt = document.createElement('option');
                        opt.value = courses[i].id;
                        opt.textContent = courses[i].title;
                        cSel.appendChild(opt);
                    }
                }
            }).catch(function() {});
        }
    },
