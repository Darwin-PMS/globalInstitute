
    /* ---------- LESSONS ---------- */
    lessons: {
        title: 'Lessons',
        api: '/lessons',
        route: 'lessons',
        columns: [
            { key: 'id', label: 'ID' },
            { key: 'title', label: 'Title', render: function(v) { return escapeHtml(v); } },
            { key: 'course', label: 'Course', render: function(v) { return v ? escapeHtml(v.title) : '-'; } },
            { key: 'module', label: 'Module', render: function(v) { return v ? escapeHtml(v.name || v.title) : '-'; } },
            { key: 'type', label: 'Type', render: function(v) { return badge(v, v === 'video' ? '#3b82f6' : v === 'quiz' ? '#8b5cf6' : v === 'assignment' ? '#f59e0b' : '#6b7280'); } },
            { key: 'sort_order', label: 'Sort', render: function(v) { return v != null ? String(v) : '-'; } },
            { key: 'status', label: 'Status', render: function(v) { return statusBadge(v); } }
        ],
        actions: function(row) {
            return '<a href="#/lessons/' + row.id + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="GenericDelete(pages.lessons,' + row.id + ')" title="Delete"><i class="bi bi-trash"></i></button>';
        },
        formFields: function(item) {
            var c = item || {};
            return selectField('course_id', 'Course', c.course_id, []) +
                selectField('module_id', 'Module', c.module_id, []) +
                inputField('title', 'Title', c.title) +
                textareaField('description', 'Description', c.description) +
                selectField('type', 'Type', c.type, [
                    { value: 'video', label: 'Video' },
                    { value: 'text', label: 'Text' },
                    { value: 'document', label: 'Document' },
                    { value: 'quiz', label: 'Quiz' },
                    { value: 'assignment', label: 'Assignment' }
                ]) +
                inputField('content_url', 'Content URL', c.content_url) +
                inputField('duration_minutes', 'Duration (minutes)', c.duration_minutes, 'number') +
                inputField('sort_order', 'Sort Order', c.sort_order, 'number') +
                '<div class="mb-3"><label class="form-label fw-semibold">Free Preview</label>' +
                '<div class="form-check"><input class="form-check-input" type="checkbox" name="is_free_preview" value="1"' + (c.is_free_preview ? ' checked' : '') + '><label class="form-check-label">Enable free preview</label></div></div>' +
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
