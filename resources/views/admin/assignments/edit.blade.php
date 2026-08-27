@extends('layouts.admin')

@section('title', 'Edit Assignment')

@section('content')
<form method="POST" action="{{ route('admin.assignments.update', $assignment->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">info</span>
            <h3>Assignment Information</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="title">Title *</label>
                <input type="text" name="title" id="title" class="form-input" value="{{ old('title', $assignment->title) }}" required placeholder="Assignment title">
                @error('title') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="course_id">Course *</label>
                    <select name="course_id" id="course_id" class="form-input" required>
                        <option value="">Select Course</option>
                        @foreach($courses ?? [] as $course)
                            <option value="{{ $course->id }}" {{ old('course_id', $assignment->course_id) == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                        @endforeach
                    </select>
                    @error('course_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="module_id">Module</label>
                    <select name="module_id" id="module_id" class="form-input">
                        <option value="">Select Module</option>
                        @foreach($modules ?? [] as $module)
                            <option value="{{ $module->id }}" data-course="{{ $module->course_id }}" {{ old('module_id', $assignment->module_id) == $module->id ? 'selected' : '' }}>{{ $module->name }}</option>
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
                            <option value="{{ $lesson->id }}" data-module="{{ $lesson->module_id }}" {{ old('lesson_id', $assignment->lesson_id) == $lesson->id ? 'selected' : '' }}>{{ $lesson->title }}</option>
                        @endforeach
                    </select>
                    @error('lesson_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="teacher_id">Teacher *</label>
                    <select name="teacher_id" id="teacher_id" class="form-input" required>
                        <option value="">Select Teacher</option>
                        @foreach($teachers ?? [] as $teacher)
                            <option value="{{ $teacher->id }}" {{ old('teacher_id', $assignment->teacher_id) == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    @error('teacher_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">description</span>
            <h3>Details</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" class="form-input" rows="4" placeholder="Assignment description...">{{ old('description', $assignment->description) }}</textarea>
                @error('description') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="instructions">Instructions</label>
                <textarea name="instructions" id="instructions" class="form-input" rows="4" placeholder="Instructions for students...">{{ old('instructions', $assignment->instructions) }}</textarea>
                @error('instructions') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="due_date">Due Date *</label>
                    <input type="datetime-local" name="due_date" id="due_date" class="form-input" value="{{ old('due_date', $assignment->due_date ? \Carbon\Carbon::parse($assignment->due_date)->format('Y-m-d\TH:i') : '') }}" required>
                    @error('due_date') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="max_marks">Max Marks *</label>
                    <input type="number" name="max_marks" id="max_marks" class="form-input" value="{{ old('max_marks', $assignment->max_marks) }}" required min="1" placeholder="e.g. 100">
                    @error('max_marks') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">upload_file</span>
            <h3>File</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="attachment">Attachment</label>
                <input type="file" name="attachment" id="attachment" class="form-input">
                @if($assignment->attachment_path)
                    <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Current: {{ basename($assignment->attachment_path) }}</div>
                @endif
                <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Upload assignment file (optional)</div>
                @error('attachment') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">settings</span>
            <h3>Status</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-input">
                    <option value="draft" {{ old('status', $assignment->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ old('status', $assignment->status) === 'published' ? 'selected' : '' }}>Published</option>
                    <option value="closed" {{ old('status', $assignment->status) === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
                @error('status') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Assignment
        </button>
        <a href="{{ route('admin.assignments.index') }}" class="btn btn-outline">Cancel</a>
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
