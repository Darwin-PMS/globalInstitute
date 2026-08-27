@extends('layouts.admin')

@section('title', 'Add Quiz')

@section('content')
<form method="POST" action="{{ route('admin.quizzes.store') }}">
    @csrf

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">info</span>
            <h3>Quiz Information</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="title">Title *</label>
                <input type="text" name="title" id="title" class="form-input" value="{{ old('title') }}" required placeholder="Quiz title">
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
            <div class="form-group">
                <label class="form-label" for="teacher_id">Teacher *</label>
                <select name="teacher_id" id="teacher_id" class="form-input" required>
                    <option value="">Select Teacher</option>
                    @foreach($teachers ?? [] as $teacher)
                        <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                    @endforeach
                </select>
                @error('teacher_id') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">settings</span>
            <h3>Settings</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" class="form-input" rows="3" placeholder="Quiz description...">{{ old('description') }}</textarea>
                @error('description') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="instructions">Instructions</label>
                <textarea name="instructions" id="instructions" class="form-input" rows="3" placeholder="Instructions for students...">{{ old('instructions') }}</textarea>
                @error('instructions') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label" for="time_limit">Time Limit (minutes) *</label>
                    <input type="number" name="time_limit" id="time_limit" class="form-input" value="{{ old('time_limit') }}" required min="1" placeholder="e.g. 30">
                    @error('time_limit') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="max_attempts">Max Attempts</label>
                    <input type="number" name="max_attempts" id="max_attempts" class="form-input" value="{{ old('max_attempts', 1) }}" min="1" placeholder="e.g. 3">
                    @error('max_attempts') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="pass_marks">Pass Marks *</label>
                    <input type="number" name="pass_marks" id="pass_marks" class="form-input" value="{{ old('pass_marks') }}" required min="0" placeholder="e.g. 50">
                    @error('pass_marks') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="total_marks">Total Marks *</label>
                    <input type="number" name="total_marks" id="total_marks" class="form-input" value="{{ old('total_marks') }}" required min="1" placeholder="e.g. 100">
                    @error('total_marks') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-input">
                        <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="closed" {{ old('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                    @error('status') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Create Quiz
        </button>
        <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline">Cancel</a>
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
            var opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.name;
            moduleSelect.appendChild(opt);
        }
    });
});
</script>
@endsection
