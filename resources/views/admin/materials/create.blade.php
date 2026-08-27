@extends('layouts.admin')

@section('title', 'Add Material')

@section('content')
<form method="POST" action="{{ route('admin.materials.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">folder_open</span>
            <h3>Material Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="course_id">Course *</label>
                    <select name="course_id" id="course_id" class="form-input" required>
                        <option value="">Select Course</option>
                        @foreach($courses ?? [] as $course)
                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                        @endforeach
                    </select>
                    @error('course_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="module_id">Module</label>
                    <select name="module_id" id="module_id" class="form-input">
                        <option value="">Select Module</option>
                        @foreach($modules ?? [] as $module)
                            <option value="{{ $module->id }}" data-course="{{ $module->course_id }}" {{ old('module_id') == $module->id ? 'selected' : '' }}>{{ $module->name }}</option>
                        @endforeach
                    </select>
                    @error('module_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="lesson_id">Lesson</label>
                    <select name="lesson_id" id="lesson_id" class="form-input">
                        <option value="">Select Lesson</option>
                        @foreach($lessons ?? [] as $lesson)
                            <option value="{{ $lesson->id }}" data-module="{{ $lesson->module_id }}" {{ old('lesson_id') == $lesson->id ? 'selected' : '' }}>{{ $lesson->title }}</option>
                        @endforeach
                    </select>
                    @error('lesson_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="title">Title *</label>
                    <input type="text" name="title" id="title" class="form-input" value="{{ old('title') }}" required placeholder="Material title">
                    @error('title') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" class="form-input" rows="3" placeholder="Brief description...">{{ old('description') }}</textarea>
                @error('description') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">upload_file</span>
            <h3>File</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="file">Upload File</label>
                    <input type="file" name="file" id="file" class="form-input">
                    <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Max size: 50MB</div>
                    @error('file') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="file_type">File Type</label>
                    <select name="file_type" id="file_type" class="form-input">
                        <option value="pdf" {{ old('file_type') === 'pdf' ? 'selected' : '' }}>PDF</option>
                        <option value="ppt" {{ old('file_type') === 'ppt' ? 'selected' : '' }}>PowerPoint (PPT)</option>
                        <option value="doc" {{ old('file_type') === 'doc' ? 'selected' : '' }}>Word (DOC)</option>
                        <option value="xls" {{ old('file_type') === 'xls' ? 'selected' : '' }}>Excel (XLS)</option>
                        <option value="zip" {{ old('file_type') === 'zip' ? 'selected' : '' }}>ZIP Archive</option>
                        <option value="image" {{ old('file_type') === 'image' ? 'selected' : '' }}>Image</option>
                        <option value="link" {{ old('file_type') === 'link' ? 'selected' : '' }}>External Link</option>
                        <option value="other" {{ old('file_type') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('file_type') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="external_url">External URL</label>
                <input type="url" name="external_url" id="external_url" class="form-input" value="{{ old('external_url') }}" placeholder="https://...">
                <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Use for external links instead of file upload</div>
                @error('external_url') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">settings</span>
            <h3>Settings</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="visibility">Visibility</label>
                    <select name="visibility" id="visibility" class="form-input">
                        <option value="public" {{ old('visibility', 'public') === 'public' ? 'selected' : '' }}>Public</option>
                        <option value="enrolled" {{ old('visibility') === 'enrolled' ? 'selected' : '' }}>Enrolled Students Only</option>
                        <option value="private" {{ old('visibility') === 'private' ? 'selected' : '' }}>Private</option>
                    </select>
                    @error('visibility') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="is_published">
                        <input type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published', '1') ? 'checked' : '' }} style="margin-right:8px;">
                        Published
                    </label>
                    <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Make this material visible to students</div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Upload Material
        </button>
        <a href="{{ route('admin.materials.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
document.getElementById('course_id').addEventListener('change', function() {
    var courseId = this.value;
    var moduleSelect = document.getElementById('module_id');
    var lessonSelect = document.getElementById('lesson_id');
    var modules = @json($modules ?? []);
    var lessons = @json($lessons ?? []);

    moduleSelect.innerHTML = '<option value="">Select Module</option>';
    modules.forEach(function(m) {
        if (!courseId || m.course_id == courseId) {
            var opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.name;
            moduleSelect.appendChild(opt);
        }
    });

    lessonSelect.innerHTML = '<option value="">Select Lesson</option>';
});

document.getElementById('module_id').addEventListener('change', function() {
    var moduleId = this.value;
    var lessonSelect = document.getElementById('lesson_id');
    var lessons = @json($lessons ?? []);
    lessonSelect.innerHTML = '<option value="">Select Lesson</option>';
    lessons.forEach(function(l) {
        if (!moduleId || l.module_id == moduleId) {
            var opt = document.createElement('option');
            opt.value = l.id;
            opt.textContent = l.title;
            lessonSelect.appendChild(opt);
        }
    });
});
</script>
@endsection
