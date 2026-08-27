@extends('layouts.frontend')

@section('title', 'Evaluate Submission')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="mb-1">Evaluate Submission</h3>
            <p class="text-muted mb-0">Review student work and provide marks & feedback.</p>
        </div>
        <a href="{{ route('teacher.submissions') }}" class="btn btn-outline-secondary">
            <span class="material-symbols-outlined me-1" style="font-size: 1rem;">arrow_back</span>
            Back to Submissions
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <span class="material-symbols-outlined me-2 align-middle">person</span>
                        Student Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar avatar-lg bg-primary bg-opacity-10 rounded-circle me-3">
                            <span class="material-symbols-outlined text-primary" style="font-size: 2rem;">person</span>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $submission->student->user->name ?? 'N/A' }}</h5>
                            <small class="text-muted">Roll: {{ $submission->student->roll_number ?? 'N/A' }}</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted d-block">Email</small>
                            <span>{{ $submission->student->user->email ?? 'N/A' }}</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Course</small>
                            <span>{{ $submission->assignment->course->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <span class="material-symbols-outlined me-2 align-middle">assignment</span>
                        Assignment Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Assignment Title</small>
                        <strong>{{ $submission->assignment->title ?? 'N/A' }}</strong>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Max Marks</small>
                            <strong class="text-primary">{{ $submission->assignment->max_marks ?? 'N/A' }}</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Due Date</small>
                            <span>{{ $submission->assignment->due_date ? \Carbon\Carbon::parse($submission->assignment->due_date)->format('M d, Y') : 'N/A' }}</span>
                        </div>
                    </div>
                    <div>
                        <small class="text-muted d-block">Submitted On</small>
                        <span class="material-symbols-outlined me-1 text-muted" style="font-size: 1rem;">calendar_today</span>
                        {{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y h:i A') : 'N/A' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <span class="material-symbols-outlined me-2 align-middle">attach_file</span>
                        Submitted File
                    </h5>
                </div>
                <div class="card-body">
                    @if($submission->file_path)
                        <div class="d-flex align-items-center p-3 bg-light rounded">
                            <span class="material-symbols-outlined text-primary me-3" style="font-size: 2.5rem;">description</span>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">{{ basename($submission->file_path) }}</h6>
                                <small class="text-muted">Submitted file</small>
                            </div>
                            <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" class="btn btn-sm btn-primary">
                                <span class="material-symbols-outlined me-1" style="font-size: 1rem;">download</span>
                                Download
                            </a>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <span class="material-symbols-outlined text-muted" style="font-size: 2rem;">folder_off</span>
                            <p class="text-muted mt-2 mb-0">No file submitted.</p>
                        </div>
                    @endif
                </div>
            </div>

            @if($submission->submission_text)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">
                            <span class="material-symbols-outlined me-2 align-middle">article</span>
                            Submission Text
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="p-3 bg-light rounded">
                            {!! nl2br(e($submission->submission_text)) !!}
                        </div>
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <span class="material-symbols-outlined me-2 align-middle">grading</span>
                        Evaluation
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('teacher.evaluate-submission', $submission->id) }}" method="POST">
                        @csrf
                        @method('POST')

                        <div class="mb-3">
                            <label for="marks" class="form-label">
                                Marks Obtained <span class="text-muted">(Max: {{ $submission->assignment->max_marks ?? 'N/A' }})</span>
                            </label>
                            <input type="number" class="form-control @error('marks') is-invalid @enderror"
                                   id="marks" name="marks"
                                   value="{{ $submission->marks ?? old('marks') }}"
                                   min="0" max="{{ $submission->assignment->max_marks ?? 100 }}" required>
                            @error('marks')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="teacher_feedback" class="form-label">Feedback</label>
                            <textarea class="form-control @error('teacher_feedback') is-invalid @enderror"
                                      id="teacher_feedback" name="teacher_feedback" rows="5"
                                      placeholder="Provide feedback for the student...">{{ $submission->teacher_feedback ?? old('teacher_feedback') }}</textarea>
                            @error('teacher_feedback')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <span class="material-symbols-outlined me-1" style="font-size: 1.1rem;">save</span>
                                Submit Evaluation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection