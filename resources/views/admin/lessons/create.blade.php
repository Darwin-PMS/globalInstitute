@extends('layouts.admin')

@section('title', 'Add Lesson')

@section('content')
<form method="POST" action="{{ route('admin.lessons.store') }}">
    @csrf

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">menu_book</span>
            <h3>Lesson Information</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="title">Title *</label>
                <input type="text" name="title" id="title" class="form-input" value="{{ old('title') }}" required placeholder="Lesson title">
                @error('title') <span class="form-error">{{ $message }}</span> @enderror
            </div>
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
                    <label class="form-label" for="module_id">Module *</label>
                    <select name="module_id" id="module_id" class="form-input" required>
                        <option value="">Select Module</option>
                        @foreach($modules ?? [] as $module)
                            <option value="{{ $module->id }}" data-course="{{ $module->course_id }}" {{ old('module_id') == $module->id ? 'selected' : '' }}>{{ $module->name }}</option>
                        @endforeach
                    </select>
                    @error('module_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" class="form-input" rows="3" placeholder="Brief description of the lesson...">{{ old('description') }}</textarea>
                @error('description') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="content">Content</label>
                <textarea name="content" id="content" class="form-input" rows="6" placeholder="Lesson content, notes, or transcript...">{{ old('content') }}</textarea>
                @error('content') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">videocam</span>
            <h3>Media</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="video_url">Video URL</label>
                <input type="url" name="video_url" id="video_url" class="form-input" value="{{ old('video_url') }}" placeholder="https://youtube.com/watch?v=...">
                @error('video_url') <span class="form-error">{{ $message }}</span> @enderror
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
                    <label class="form-label" for="duration">Duration</label>
                    <input type="text" name="duration" id="duration" class="form-input" value="{{ old('duration') }}" placeholder="e.g. 30 min">
                    @error('duration') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="sequence">Sequence Order</label>
                    <input type="number" name="sequence" id="sequence" class="form-input" value="{{ old('sequence') }}" placeholder="e.g. 1">
                    @error('sequence') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="is_free">
                        <input type="checkbox" name="is_free" id="is_free" value="1" {{ old('is_free') ? 'checked' : '' }} style="margin-right:8px;">
                        Free Lesson
                    </label>
                    <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Allow access without enrollment</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="is_published">
                        <input type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published', '1') ? 'checked' : '' }} style="margin-right:8px;">
                        Published
                    </label>
                    <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Make this lesson visible to students</div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-input">
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Create Lesson
        </button>
        <a href="{{ route('admin.lessons.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
document.getElementById('course_id').addEventListener('change', function() {
    var courseId = this.value;
    var moduleSelect = document.getElementById('module_id');
    var modules = @json($modules ?? []);
    moduleSelect.innerHTML = '<option value="">Select Module</option>';
    modules.forEach(function(m) {
        if (!courseId || m.course_id == courseId) {
            var option = document.createElement('option');
            option.value = m.id;
            option.textContent = m.name;
            if (m.id == '{{ old("module_id") }}') option.selected = true;
            moduleSelect.appendChild(option);
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('course_id').value) {
        document.getElementById('course_id').dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
