@extends('layouts.admin')

@section('title', 'Add Question')

@section('actions')
    <a href="{{ route('admin.quizzes.edit', $quiz->id) }}" class="btn btn-outline btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back to Quiz
    </a>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <h1>Add Question to: {{ $quiz->title }}</h1>
        <p class="page-header-desc">Create a new question for this quiz.</p>
    </div>
</div>

<form method="POST" action="{{ route('admin.quizzes.store-question', $quiz->id) }}">
    @csrf

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">help</span>
            <h3>Question Details</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="question_text">Question Text *</label>
                <textarea name="question_text" id="question_text" class="form-input" rows="4" required placeholder="Enter your question...">{{ old('question_text') }}</textarea>
                @error('question_text') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="question_type">Question Type *</label>
                    <select name="question_type" id="question_type" class="form-input" required onchange="toggleQuestionType()">
                        <option value="mcq" {{ old('question_type') === 'mcq' ? 'selected' : '' }}>Multiple Choice (MCQ)</option>
                        <option value="true_false" {{ old('question_type') === 'true_false' ? 'selected' : '' }}>True / False</option>
                        <option value="short_answer" {{ old('question_type') === 'short_answer' ? 'selected' : '' }}>Short Answer</option>
                    </select>
                    @error('question_type') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="marks">Marks *</label>
                    <input type="number" name="marks" id="marks" class="form-input" value="{{ old('marks') }}" required min="1" placeholder="e.g. 10">
                    @error('marks') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;" id="mcq-options">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">radio_button_checked</span>
            <h3>Options (MCQ)</h3>
        </div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:16px;">
                @for($i = 0; $i < 4; $i++)
                <div style="display:flex;align-items:center;gap:12px;">
                    <input type="radio" name="correct_option" value="{{ $i }}" {{ old('correct_option') == $i ? 'checked' : '' }} style="flex-shrink:0;" title="Mark as correct">
                    <input type="text" name="options[]" class="form-input" value="{{ old('options')[$i] ?? '' }}" placeholder="Option {{ $i + 1 }}" style="flex:1;">
                </div>
                @endfor
                @error('options') <span class="form-error">{{ $message }}</span> @enderror
                @error('correct_option') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div style="font-size:12px;color:var(--gray-500);margin-top:8px;">Select the radio button next to the correct answer.</div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;display:none;" id="tf-options">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">radio_button_checked</span>
            <h3>Answer (True / False)</h3>
        </div>
        <div class="card-body">
            <div style="display:flex;gap:24px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:var(--gray-700);cursor:pointer;">
                    <input type="radio" name="correct_option_tf" value="true" {{ old('correct_option_tf') === 'true' ? 'checked' : '' }}>
                    True
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;color:var(--gray-700);cursor:pointer;">
                    <input type="radio" name="correct_option_tf" value="false" {{ old('correct_option_tf') === 'false' ? 'checked' : '' }}>
                    False
                </label>
            </div>
            @error('correct_option_tf') <span class="form-error">{{ $message }}</span> @enderror
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Add Question
        </button>
        <a href="{{ route('admin.quizzes.edit', $quiz->id) }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
function toggleQuestionType() {
    var type = document.getElementById('question_type').value;
    document.getElementById('mcq-options').style.display = type === 'mcq' ? 'block' : 'none';
    document.getElementById('tf-options').style.display = type === 'true_false' ? 'block' : 'none';
}
toggleQuestionType();
</script>
@endsection
