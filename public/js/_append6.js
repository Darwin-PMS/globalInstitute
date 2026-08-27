
    /* ---------- RECORDINGS ---------- */
    recordings: {
        title: 'Recordings',
        api: '/recordings',
        route: 'recordings',
        columns: [
            { key: 'id', label: 'ID' },
            { key: 'title', label: 'Title', render: function(v) { return escapeHtml(v); } },
            { key: 'course', label: 'Course', render: function(v) { return v ? escapeHtml(v.title) : '-'; } },
            { key: 'online_class', label: 'Online Class', render: function(v) { return v ? escapeHtml(v.title) : '-'; } },
            { key: 'duration', label: 'Duration', render: function(v) { return v != null ? v + ' min' : '-'; } },
            { key: 'status', label: 'Status', render: function(v) { return statusBadge(v); } }
        ],
        actions: function(row) {
            return '<a href="#/recordings/' + row.id + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="GenericDelete(pages.recordings,' + row.id + ')" title="Delete"><i class="bi bi-trash"></i></button>';
        },
        formFields: function(item) {
            var c = item || {};
            return selectField('course_id', 'Course', c.course_id, []) +
                selectField('online_class_id', 'Online Class', c.online_class_id, []) +
                inputField('title', 'Title', c.title) +
                textareaField('description', 'Description', c.description) +
                inputField('file_path', 'File Path / URL', c.file_path) +
                inputField('duration', 'Duration (minutes)', c.duration, 'number') +
                statusSelect('status', 'Status', c.status);
        },
        formDataProvider: function() {
            return Promise.all([
                Api.get('/courses?per_page=100'),
                Api.get('/online-classes?per_page=100')
            ]).then(function(responses) {
                var courses = responses[0].data || responses[0] || [];
                var classes = responses[1].data || responses[1] || [];
                var cSel = document.querySelector('[name="course_id"]');
                var ocSel = document.querySelector('[name="online_class_id"]');
                if (cSel) {
                    for (var i = 0; i < courses.length; i++) {
                        var opt = document.createElement('option');
                        opt.value = courses[i].id;
                        opt.textContent = courses[i].title;
                        cSel.appendChild(opt);
                    }
                }
                if (ocSel) {
                    for (var j = 0; j < classes.length; j++) {
                        var opt2 = document.createElement('option');
                        opt2.value = classes[j].id;
                        opt2.textContent = classes[j].title;
                        ocSel.appendChild(opt2);
                    }
                }
            }).catch(function() {});
        }
    },
