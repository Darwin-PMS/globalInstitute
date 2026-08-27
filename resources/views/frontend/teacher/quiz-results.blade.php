@extends('layouts.frontend')

@section('title', 'Quiz Results')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="mb-1">Quiz Results</h3>
            <p class="text-muted mb-0">View all student quiz attempts and scores.</p>
        </div>
        <a href="{{ route('teacher.dashboard') }}" class="btn btn-outline-secondary">
            <span class="material-symbols-outlined me-1" style="font-size: 1rem;">arrow_back</span>
            Dashboard
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 ps-3">#</th>
                            <th class="border-0">Student Name</th>
                            <th class="border-0">Quiz Title</th>
                            <th class="border-0">Course</th>
                            <th class="border-0">Attempt Date</th>
                            <th class="border-0">Score</th>
                            <th class="border-0">Status</th>
                            <th class="border-0 text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($quizResults) && count($quizResults) > 0)
                            @foreach($quizResults as $index => $result)
                                <tr>
                                    <td class="ps-3">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm bg-primary bg-opacity-10 rounded-circle me-2">
                                                <span class="material-symbols-outlined text-primary" style="font-size: 1.1rem;">person</span>
                                            </div>
                                            <strong>{{ $result->student->user->name ?? 'N/A' }}</strong>
                                        </div>
                                    </td>
                                    <td>{{ $result->quiz->title ?? 'N/A' }}</td>
                                    <td>{{ $result->quiz->course->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="material-symbols-outlined me-1 text-muted" style="font-size: 1rem;">calendar_today</span>
                                        {{ $result->attempted_at ? $result->attempted_at->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td>
                                        <strong>{{ $result->score ?? 0 }} / {{ $result->quiz->total_marks ?? 'N/A' }}</strong>
                                    </td>
                                    <td>
                                        @if($result->is_pass)
                                            <span class="badge bg-success">Pass</span>
                                        @else
                                            <span class="badge bg-danger">Fail</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#quizDetailModal{{ $result->id }}">
                                            <span class="material-symbols-outlined me-1" style="font-size: 1rem;">visibility</span>
                                            View
                                        </button>
                                    </td>
                                </tr>

                                <!-- Detail Modal -->
                                <div class="modal fade" id="quizDetailModal{{ $result->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <span class="material-symbols-outlined me-2 align-middle">quiz</span>
                                                    Quiz Detail
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Student</small>
                                                        <strong>{{ $result->student->user->name ?? 'N/A' }}</strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Quiz</small>
                                                        <strong>{{ $result->quiz->title ?? 'N/A' }}</strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Course</small>
                                                        <span>{{ $result->quiz->course->name ?? 'N/A' }}</span>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Attempt Date</small>
                                                        <span>{{ $result->attempted_at ? $result->attempted_at->format('M d, Y h:i A') : 'N/A' }}</span>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Score</small>
                                                        <strong class="text-primary fs-5">{{ $result->score ?? 0 }} / {{ $result->quiz->total_marks ?? 'N/A' }}</strong>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Status</small>
                                                        @if($result->is_pass)
                                                            <span class="badge bg-success fs-6">Pass</span>
                                                        @else
                                                            <span class="badge bg-danger fs-6">Fail</span>
                                                        @endif
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Time Taken</small>
                                                        <span>{{ $result->time_taken ?? 'N/A' }} minutes</span>
                                                    </div>
                                                    <div class="col-6">
                                                        <small class="text-muted d-block">Correct Answers</small>
                                                        <span>{{ $result->correct_answers ?? 'N/A' }} / {{ $result->total_questions ?? 'N/A' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <span class="material-symbols-outlined text-muted" style="font-size: 3rem;">quiz</span>
                                    <p class="text-muted mt-2 mb-0">No quiz results found.</p>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection