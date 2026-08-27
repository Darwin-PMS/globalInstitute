@extends('layouts.admin')

@section('title', 'Edit Quiz')

@section('content')
<form method="POST" action="{{ route('admin.quizzes.update', $quiz->id) }}">
    @csrf
    @method('PUT')

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">info</span>
            <h3>Quiz Information</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="title">Title *</label>
                <input type="text" name="title" id="title" class="form-input" value="{{ old('title', $quiz->title) }}" required placeholder="Quiz title">
                @error('title') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="course_id">Course *</label>
                    <select name="course_id" id="course_id" class="form-input" required>
                        <option value="">Select Course</option>
                        @foreach($courses ?? [] as $course)
                            <option value="{{ $course->id }}" {{ old('course_id', $quiz->course_id) == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                        @endforeach
                    </select>
                    @error('course_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="module_id">Module</label>
                    <select name="module_id" id="module_id" class="form-input">
                        <option value="">Select Module</option>
                        @foreach($modules ?? [] as $module)
                            <option value="{{ $module->id }}" data-course="{{ $module->course_id }}" {{ old('module_id', $quiz->module_id) == $module->id ? 'selected' : '' }}>{{ $module->name }}</option>
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
                        <option value="{{ $teacher->id }}" {{ old('teacher_id', $quiz->teacher_id) == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
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
                <textarea name="description" id="description" class="form-input" rows="3" placeholder="Quiz description...">{{ old('description', $quiz->description) }}</textarea>
                @error('description') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="instructions">Instructions</label>
                <textarea name="instructions" id="instructions" class="form-input" rows="3" placeholder="Instructions for students...">{{ old('instructions', $quiz->instructions) }}</textarea>
                @error('instructions') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label" for="time_limit">Time Limit (minutes) *</label>
                    <input type="number" name="time_limit" id="time_limit" class="form-input" value="{{ old('time_limit', $quiz->time_limit) }}" required min="1" placeholder="e.g. 30">
                    @error('time_limit') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="max_attempts">Max Attempts</label>
                    <input type="number" name="max_attempts" id="max_attempts" class="form-input" value="{{ old('max_attempts', $quiz->max_attempts) }}" min="1" placeholder="e.g. 3">
                    @error('max_attempts') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="pass_marks">Pass Marks *</label>
                    <input type="number" name="pass_marks" id="pass_marks" class="form-input" value="{{ old('pass_marks', $quiz->pass_marks) }}" required min="0" placeholder="e.g. 50">
                    @error('pass_marks') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="total_marks">Total Marks *</label>
                    <input type="number" name="total_marks" id="total_marks" class="form-input" value="{{ old('total_marks', $quiz->total_marks) }}" required min="1" placeholder="e.g. 100">
                    @error('total_marks') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-input">
                        <option value="draft" {{ old('status', $quiz->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status', $quiz->status) === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="closed" {{ old('status', $quiz->status) === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                    @error('status') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Quiz
        </button>
        <a href="{{ route('admin.quizzes.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

@if(isset($quiz->questions) && $quiz->questions->count())
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <span class="material-symbols-outlined" style="color:var(--primary);">help</span>
        <h3>Questions ({{ $quiz->questions->count() }})</h3>
        <a href="{{ route('admin.quizzes.add-question', $quiz->id) }}" class="btn btn-primary btn-sm" style="margin-left:auto;">
            <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Question
        </a>
    </div>
    <div class="card-body" style="padding:0;">
        @foreach($quiz->questions as $question)
            <div style="padding:20px 24px;border-bottom:1px solid var(--gray-200);{{ $loop->last ? 'border-bottom:none;' : '' }}">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                            <span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--gray-400);">Q{{ $loop->iteration }}</span>
                            <span class="badge badge-secondary" style="font-size:11px;">{{ strtoupper(str_replace('_', ' ', $question->question_type)) }}</span>
                            <span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--gray-500);">{{ $question->marks }} marks</span>
                        </div>
                        <div style="font-weight:600;color:var(--gray-900);font-size:14px;margin-bottom:8px;">{{ $question->question_text }}</div>
                        @if($question->question_type === 'mcq' && isset($question->options))
                            <div style="display:flex;flex-direction:column;gap:4px;margin-top:8px;">
                                @foreach($question->options as $i => $option)
                                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--gray-600);">
                                        <span style="width:20px;height:20px;border-radius:50%;border:2px solid {{ isset($question->correct_option) && $question->correct_option == $i ? 'var(--success)' : 'var(--gray-300)' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            @if(isset($question->correct_option) && $question->correct_option == $i)
                                                <span class="material-symbols-outlined" style="font-size:14px;color:var(--success);">check</span>
                                            @endif
                                        </span>
                                        {{ $option }}
                                    </div>
                                @endforeach
                            </div>
                        @elseif($question->question_type === 'true_false')
                            <div style="display:flex;gap:12px;margin-top:8px;">
                                <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--gray-600);">
                                    <span style="width:20px;height:20px;border-radius:50%;border:2px solid {{ ($question->correct_option ?? '') === 'true' ? 'var(--success)' : 'var(--gray-300)' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        @if(($question->correct_option ?? '') === 'true')
                                            <span class="material-symbols-outlined" style="font-size:14px;color:var(--success);">check</span>
                                        @endif
                                    </span> True
                                </div>
                                <div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--gray-600);">
                                    <span style="width:20px;height:20px;border-radius:50%;border:2px solid {{ ($question->correct_option ?? '') === 'false' ? 'var(--success)' : 'var(--gray-300)' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        @if(($question->correct_option ?? '') === 'false')
                                            <span class="material-symbols-outlined" style="font-size:14px;color:var(--success);">check</span>
                                        @endif
                                    </span> False
                                </div>
                            </div>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('admin.quizzes.destroy-question', [$quiz->id, $question->id]) }}" onsubmit="return confirm('Delete this question?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-btn" title="Delete" style="color:var(--danger);">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>
@else
<div class="card" style="margin-bottom:24px;">
    <div class="card-body" style="padding:60px 0;text-align:center;">
        <div class="empty-state">
            <span class="material-symbols-outlined" style="font-size:48px;color:var(--gray-300);">help</span>
            <h3>No questions yet</h3>
            <p>Add questions to this quiz.</p>
            <a href="{{ route('admin.quizzes.add-question', $quiz->id) }}" class="btn btn-primary btn-sm">
                <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Question
            </a>
        </div>
    </div>
</div>
@endif

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
