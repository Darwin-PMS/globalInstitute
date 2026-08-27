@extends('layouts.frontend')

@section('title', 'Take Quiz: ' . ($quiz->title ?? ''))

@section('content')
<section class="section">
    <div class="container">

        <div class="quiz-header">
            <h2>{{ $quiz->title ?? 'Quiz' }}</h2>
            <p class="quiz-course">{{ $quiz->course->title ?? 'N/A' }}</p>
        </div>

        <div class="quiz-instructions card">
            <div class="card-body">
                <h4>
                    <span class="material-symbols-outlined">info</span> Instructions
                </h4>
                <ul>
                    <li>Total Questions: {{ $quiz->questions->count() ?? 0 }}</li>
                    <li>Time Limit: {{ $quiz->time_limit ?? 'N/A' }} minutes</li>
                    <li>Total Marks: {{ $quiz->total_marks ?? 'N/A' }}</li>
                    <li>Pass Marks: {{ $quiz->pass_marks ?? 'N/A' }}</li>
                    <li>You can only attempt this quiz once.</li>
                    <li>The quiz will auto-submit when the time runs out.</li>
                </ul>
            </div>
        </div>

        <div class="quiz-timer" id="quizTimer">
            <span class="material-symbols-outlined">timer</span>
            <span id="countdown">{{ $quiz->time_limit ?? 60 }}:00</span>
        </div>

        <form action="{{ route('student.submit-quiz', $quiz->id) }}" method="POST" id="quizForm">
            @csrf
            <div class="quiz-questions">
                @forelse($quiz->questions ?? [] as $questionIndex => $question)
                    <div class="card question-card">
                        <div class="card-body">
                            <h4 class="question-number">Question {{ $questionIndex + 1 }}</h4>
                            <p class="question-text">{{ $question->question_text }}</p>
                            <div class="question-options">
                                @foreach($question->options ?? [] as $optionIndex => $option)
                                    <label class="option-label">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option['id'] ?? $optionIndex }}">
                                        <span class="option-text">{{ $option['text'] ?? $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <span class="material-symbols-outlined">quiz</span>
                        <p>No questions available for this quiz.</p>
                    </div>
                @endforelse
            </div>

            <div class="quiz-submit">
                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                    <span class="material-symbols-outlined">send</span> Submit Quiz
                </button>
            </div>
        </form>

    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const timeLimit = {{ $quiz->time_limit ?? 60 }};
        let totalSeconds = timeLimit * 60;
        const countdownEl = document.getElementById('countdown');
        const form = document.getElementById('quizForm');

        const timer = setInterval(function() {
            totalSeconds--;
            if (totalSeconds <= 0) {
                clearInterval(timer);
                form.submit();
                return;
            }
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            countdownEl.textContent =
                String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        }, 1000);

        form.addEventListener('submit', function() {
            clearInterval(timer);
        });
    });
</script>
@endsection
