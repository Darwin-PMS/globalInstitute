
    /* ---------- ONLINE CLASSES ---------- */
    'online-classes': {
        title: 'Online Classes',
        api: '/online-classes',
        route: 'online-classes',
        columns: [
            { key: 'id', label: 'ID' },
            { key: 'title', label: 'Title', render: function(v) { return escapeHtml(v); } },
            { key: 'course', label: 'Course', render: function(v) { return v ? escapeHtml(v.title) : '-'; } },
            { key: 'teacher', label: 'Teacher', render: function(v) { return v ? escapeHtml(v.name) : '-'; } },
            { key: 'scheduled_at', label: 'Scheduled At', render: function(v) { return v ? new Date(v).toLocaleString() : '-'; } },
            { key: 'status', label: 'Status', render: function(v) { return statusBadge(v); } }
        ],
        actions: function(row) {
            return '<a href="#/online-classes/' + row.id + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="GenericDelete(pages[\'online-classes\'],' + row.id + ')" title="Delete"><i class="bi bi-trash"></i></button>';
        },
        formFields: function(item) {
            var c = item || {};
            return selectField('course_id', 'Course', c.course_id, []) +
                selectField('teacher_id', 'Teacher', c.teacher_id, []) +
                inputField('title', 'Title', c.title) +
                textareaField('description', 'Description', c.description) +
                inputField('scheduled_at', 'Scheduled At', c.scheduled_at ? c.scheduled_at.substring(0, 16) : '', 'datetime-local') +
                inputField('duration_minutes', 'Duration (minutes)', c.duration_minutes, 'number') +
                selectField('meeting_provider', 'Meeting Provider', c.meeting_provider, [
                    { value: 'zoom', label: 'Zoom' },
                    { value: 'google_meet', label: 'Google Meet' },
                    { value: 'ms_teams', label: 'MS Teams' },
                    { value: 'external_link', label: 'External Link' }
                ]) +
                inputField('meeting_link', 'Meeting Link', c.meeting_link) +
                selectField('class_type', 'Class Type', c.class_type, [
                    { value: 'live', label: 'Live' },
                    { value: 'recorded', label: 'Recorded' },
                    { value: 'hybrid', label: 'Hybrid' }
                ]) +
                selectField('recurrence', 'Recurrence', c.recurrence, [
                    { value: 'none', label: 'None' },
                    { value: 'daily', label: 'Daily' },
                    { value: 'weekly', label: 'Weekly' },
                    { value: 'biweekly', label: 'Bi-weekly' },
                    { value: 'monthly', label: 'Monthly' }
                ]) +
                statusSelect('status', 'Status', c.status);
        },
        formDataProvider: function() {
            return Promise.all([
                Api.get('/courses?per_page=100'),
                Api.get('/teachers?per_page=100')
            ]).then(function(responses) {
                var courses = responses[0].data || responses[0] || [];
                var teachers = responses[1].data || responses[1] || [];
                var cSel = document.querySelector('[name="course_id"]');
                var tSel = document.querySelector('[name="teacher_id"]');
                if (cSel) {
                    for (var i = 0; i < courses.length; i++) {
                        var opt = document.createElement('option');
                        opt.value = courses[i].id;
                        opt.textContent = courses[i].title;
                        cSel.appendChild(opt);
                    }
                }
                if (tSel) {
                    for (var j = 0; j < teachers.length; j++) {
                        var opt2 = document.createElement('option');
                        opt2.value = teachers[j].id;
                        opt2.textContent = teachers[j].name;
                        tSel.appendChild(opt2);
                    }
                }
            }).catch(function() {});
        }
    },
