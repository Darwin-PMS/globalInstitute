
    /* ---------- MATERIALS ---------- */
    materials: {
        title: 'Materials',
        api: '/materials',
        route: 'materials',
        columns: [
            { key: 'id', label: 'ID' },
            { key: 'title', label: 'Title', render: function(v) { return escapeHtml(v); } },
            { key: 'course', label: 'Course', render: function(v) { return v ? escapeHtml(v.title) : '-'; } },
            { key: 'type', label: 'Type', render: function(v) { return badge(v, v === 'video' ? '#3b82f6' : v === 'pdf' ? '#ef4444' : v === 'document' ? '#6b7280' : v === 'image' ? '#10b981' : '#8b5cf6'); } },
            { key: 'visibility', label: 'Visibility', render: function(v) { return badge(v, v === 'public' ? '#10b981' : v === 'enrolled' ? '#3b82f6' : '#6b7280'); } },
            { key: 'status', label: 'Status', render: function(v) { return statusBadge(v); } }
        ],
        actions: function(row) {
            return '<a href="#/materials/' + row.id + '" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>' +
                '<button class="btn btn-sm btn-outline-danger" onclick="GenericDelete(pages.materials,' + row.id + ')" title="Delete"><i class="bi bi-trash"></i></button>';
        },
        formFields: function(item) {
            var c = item || {};
            return selectField('course_id', 'Course', c.course_id, []) +
                inputField('title', 'Title', c.title) +
                textareaField('description', 'Description', c.description) +
                selectField('type', 'Type', c.type, [
                    { value: 'pdf', label: 'PDF' },
                    { value: 'video', label: 'Video' },
                    { value: 'document', label: 'Document' },
                    { value: 'link', label: 'Link' },
                    { value: 'image', label: 'Image' }
                ]) +
                inputField('file_path', 'File Path / URL', c.file_path) +
                selectField('visibility', 'Visibility', c.visibility, [
                    { value: 'public', label: 'Public' },
                    { value: 'enrolled', label: 'Enrolled Only' },
                    { value: 'private', label: 'Private' }
                ]) +
                inputField('sort_order', 'Sort Order', c.sort_order, 'number') +
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
