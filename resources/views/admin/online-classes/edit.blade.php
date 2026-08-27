@extends('layouts.admin')

@section('title', 'Edit Online Class')

@section('content')
<form method="POST" action="{{ route('admin.online-classes.update', $onlineClass->id) }}">
    @csrf
    @method('PUT')

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">videocam</span>
            <h3>Class Information</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="title">Title *</label>
                <input type="text" name="title" id="title" class="form-input" value="{{ old('title', $onlineClass->title) }}" required>
                @error('title') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="course_id">Course *</label>
                    <select name="course_id" id="course_id" class="form-input" required>
                        <option value="">Select Course</option>
                        @foreach($courses ?? [] as $course)
                            <option value="{{ $course->id }}" {{ old('course_id', $onlineClass->course_id) == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                        @endforeach
                    </select>
                    @error('course_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="batch_id">Batch *</label>
                    <select name="batch_id" id="batch_id" class="form-input" required>
                        <option value="">Select Batch</option>
                        @foreach($batches ?? [] as $batch)
                            <option value="{{ $batch->id }}" data-course="{{ $batch->course_id }}" {{ old('batch_id', $onlineClass->batch_id) == $batch->id ? 'selected' : '' }}>{{ $batch->batch_name }}</option>
                        @endforeach
                    </select>
                    @error('batch_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="module_id">Module</label>
                    <select name="module_id" id="module_id" class="form-input">
                        <option value="">Select Module</option>
                        @foreach($modules ?? [] as $module)
                            <option value="{{ $module->id }}" data-course="{{ $module->course_id }}" {{ old('module_id', $onlineClass->module_id) == $module->id ? 'selected' : '' }}>{{ $module->name }}</option>
                        @endforeach
                    </select>
                    @error('module_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="lesson_id">Lesson</label>
                    <select name="lesson_id" id="lesson_id" class="form-input">
                        <option value="">Select Lesson</option>
                        @foreach($lessons ?? [] as $lesson)
                            <option value="{{ $lesson->id }}" data-module="{{ $lesson->module_id }}" {{ old('lesson_id', $onlineClass->lesson_id) == $lesson->id ? 'selected' : '' }}>{{ $lesson->title }}</option>
                        @endforeach
                    </select>
                    @error('lesson_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="teacher_id">Teacher *</label>
                <select name="teacher_id" id="teacher_id" class="form-input" required>
                    <option value="">Select Teacher</option>
                    @foreach($teachers ?? [] as $teacher)
                        <option value="{{ $teacher->id }}" {{ old('teacher_id', $onlineClass->teacher_id) == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                    @endforeach
                </select>
                @error('teacher_id') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">calendar_month</span>
            <h3>Schedule</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label" for="class_date">Class Date *</label>
                    <input type="date" name="class_date" id="class_date" class="form-input" value="{{ old('class_date', $onlineClass->class_date) }}" required>
                    @error('class_date') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="start_time">Start Time *</label>
                    <input type="time" name="start_time" id="start_time" class="form-input" value="{{ old('start_time', $onlineClass->start_time) }}" required>
                    @error('start_time') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="end_time">End Time *</label>
                    <input type="time" name="end_time" id="end_time" class="form-input" value="{{ old('end_time', $onlineClass->end_time) }}" required>
                    @error('end_time') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="duration">Duration</label>
                <input type="text" name="duration" id="duration" class="form-input" value="{{ old('duration', $onlineClass->duration) }}" placeholder="e.g. 1 hour 30 min">
                @error('duration') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">link</span>
            <h3>Meeting Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="meeting_provider">Meeting Provider</label>
                    <select name="meeting_provider" id="meeting_provider" class="form-input">
                        <option value="">Select Provider</option>
                        <option value="zoom" {{ old('meeting_provider', $onlineClass->meeting_provider) === 'zoom' ? 'selected' : '' }}>Zoom</option>
                        <option value="google_meet" {{ old('meeting_provider', $onlineClass->meeting_provider) === 'google_meet' ? 'selected' : '' }}>Google Meet</option>
                        <option value="microsoft_teams" {{ old('meeting_provider', $onlineClass->meeting_provider) === 'microsoft_teams' ? 'selected' : '' }}>Microsoft Teams</option>
                        <option value="webex" {{ old('meeting_provider', $onlineClass->meeting_provider) === 'webex' ? 'selected' : '' }}>Webex</option>
                        <option value="other" {{ old('meeting_provider', $onlineClass->meeting_provider) === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('meeting_provider') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="meeting_url">Meeting URL</label>
                    <input type="url" name="meeting_url" id="meeting_url" class="form-input" value="{{ old('meeting_url', $onlineClass->meeting_url) }}" placeholder="https://...">
                    @error('meeting_url') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="meeting_id">Meeting ID</label>
                    <input type="text" name="meeting_id" id="meeting_id" class="form-input" value="{{ old('meeting_id', $onlineClass->meeting_id) }}" placeholder="e.g. 123-456-789">
                    @error('meeting_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="meeting_password">Meeting Password</label>
                    <input type="text" name="meeting_password" id="meeting_password" class="form-input" value="{{ old('meeting_password', $onlineClass->meeting_password) }}" placeholder="e.g. abc123">
                    @error('meeting_password') <span class="form-error">{{ $message }}</span> @enderror
                </div>
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
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-input">
                        <option value="scheduled" {{ old('status', $onlineClass->status) === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="live" {{ old('status', $onlineClass->status) === 'live' ? 'selected' : '' }}>Live</option>
                        <option value="completed" {{ old('status', $onlineClass->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ old('status', $onlineClass->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        <option value="postponed" {{ old('status', $onlineClass->status) === 'postponed' ? 'selected' : '' }}>Postponed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="is_attendance_required">
                        <input type="checkbox" name="is_attendance_required" id="is_attendance_required" value="1" {{ old('is_attendance_required', $onlineClass->is_attendance_required) ? 'checked' : '' }} style="margin-right:8px;">
                        Attendance Required
                    </label>
                    <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Track student attendance for this class</div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Class
        </button>
        <a href="{{ route('admin.online-classes.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
document.getElementById('course_id').addEventListener('change', function() {
    var courseId = this.value;
    var batchSelect = document.getElementById('batch_id');
    var moduleSelect = document.getElementById('module_id');
    var lessonSelect = document.getElementById('lesson_id');
    var batches = @json($batches ?? []);
    var modules = @json($modules ?? []);
    var lessons = @json($lessons ?? []);

    batchSelect.innerHTML = '<option value="">Select Batch</option>';
    batches.forEach(function(b) {
        if (!courseId || b.course_id == courseId) {
            var opt = document.createElement('option');
            opt.value = b.id;
            opt.textContent = b.batch_name;
            batchSelect.appendChild(opt);
        }
    });

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
