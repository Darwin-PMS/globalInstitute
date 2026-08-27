@extends('layouts.frontend')

@section('title', 'My Quizzes')

@section('content')
<section class="section">
    <div class="container">

        <div class="section-header">
            <h3>My Quizzes</h3>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Course</th>
                        <th>Time Limit</th>
                        <th>Total Marks</th>
                        <th>Pass Marks</th>
                        <th>Attempts</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quizzes ?? [] as $quiz)
                        @php
                            $attemptsUsed = $quiz->attempts_used ?? 0;
                            $maxAttempts = $quiz->max_attempts ?? 1;
                            $quizStatus = $quiz->status ?? 'Not Attempted';
                            $statusClass = strtolower(str_replace(' ', '-', $quizStatus));
                        @endphp
                        <tr>
                            <td>{{ $quiz->title }}</td>
                            <td>{{ $quiz->course->title ?? 'N/A' }}</td>
                            <td>{{ $quiz->time_limit ?? 'N/A' }} min</td>
                            <td>{{ $quiz->total_marks ?? 'N/A' }}</td>
                            <td>{{ $quiz->pass_marks ?? 'N/A' }}</td>
                            <td>{{ $attemptsUsed }} / {{ $maxAttempts }}</td>
                            <td>
                                <span class="badge badge-{{ $statusClass }}">{{ $quizStatus }}</span>
                            </td>
                            <td>
                                @if($quizStatus === 'Not Attempted' && $attemptsUsed < $maxAttempts)
                                    <a href="{{ route('student.take-quiz', $quiz->id) }}" class="btn btn-primary btn-sm">
                                        <span class="material-symbols-outlined">quiz</span> Take Quiz
                                    </a>
                                @else
                                    <a href="{{ route('student.view-quiz-result', $quiz->id) }}" class="btn btn-outline btn-sm">
                                        <span class="material-symbols-outlined">visibility</span> View Result
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No quizzes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</section>
@endsection
