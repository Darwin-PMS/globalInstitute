
    /* ---------- QUIZZES ---------- */
    quizzes: {
        title: 'Quizzes',
        api: '/quizzes',
        route: 'quizzes',
        columns: [
            { key: 'id', label: 'ID' },
            { key: 'title', label: 'Title', render: function(v) { return escapeHtml(v); } },
            { key: 'course', label: 'Course', render: function(v) { return v ? escapeHtml(v.title) : '-'; } },
            { key: 'lesson', label: 'Lesson', render: function(v) { return v ? escapeHtml(v.title) : '-'; } },
            { key: 'time_limit', label: 'Time Limit', render: function(v) { return v != null ? v + ' min' : '-'; } },
            { key: 'passing_marks', label: 'Passing Marks', render: function(v) { return v != null ? String(v) : '-'; } },
            { key: 'status', label: 'Status', render: function(v) { return statusBadge(v); } }
        ],
        actions: function(row) {
            return '<a href="#/quizzes/' + row.id + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="GenericDelete(pages.quizzes,' + row.id + ')" title="Delete"><i class="bi bi-trash"></i></button>';
        },
        formFields: function(item) {
            var c = item || {};
            return selectField('course_id', 'Course', c.course_id, []) +
                selectField('lesson_id', 'Lesson', c.lesson_id, []) +
                inputField('title', 'Title', c.title) +
                textareaField('description', 'Description', c.description) +
                inputField('time_limit', 'Time Limit (minutes)', c.time_limit, 'number') +
                inputField('passing_marks', 'Passing Marks', c.passing_marks, 'number') +
                inputField('max_attempts', 'Max Attempts', c.max_attempts, 'number') +
                '<div class="mb-3"><label class="form-label fw-semibold">Shuffle Questions</label>' +
                '<div class="form-check"><input class="form-check-input" type="checkbox" name="shuffle_questions" value="1"' + (c.shuffle_questions ? ' checked' : '') + '><label class="form-check-label">Enable shuffle</label></div></div>' +
                statusSelect('status', 'Status', c.status);
        },
        formDataProvider: function() {
            return Promise.all([
                Api.get('/courses?per_page=100'),
                Api.get('/lessons?per_page=100')
            ]).then(function(responses) {
                var courses = responses[0].data || responses[0] || [];
                var lessons = responses[1].data || responses[1] || [];
                var cSel = document.querySelector('[name="course_id"]');
                var lSel = document.querySelector('[name="lesson_id"]');
                if (cSel) {
                    for (var i = 0; i < courses.length; i++) {
                        var opt = document.createElement('option');
                        opt.value = courses[i].id;
                        opt.textContent = courses[i].title;
                        cSel.appendChild(opt);
                    }
                }
                if (lSel) {
                    for (var j = 0; j < lessons.length; j++) {
                        var opt2 = document.createElement('option');
                        opt2.value = lessons[j].id;
                        opt2.textContent = lessons[j].title;
                        lSel.appendChild(opt2);
                    }
                }
            }).catch(function() {});
        }
    },
