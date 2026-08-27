@extends('layouts.frontend')

@section('title', 'Assignment Submissions')

@section('content')
<div class="container-fluid py-4">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="mb-1">Assignment Submissions</h3>
            <p class="text-muted mb-0">Review and evaluate student submissions.</p>
        </div>
        <a href="{{ route('teacher.dashboard') }}" class="btn btn-outline-secondary">
            <span class="material-symbols-outlined me-1" style="font-size: 1rem;">arrow_back</span>
            Dashboard
        </a>
    </div>

    <!-- Submissions Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 ps-3">#</th>
                            <th class="border-0">Student Name</th>
                            <th class="border-0">Assignment Title</th>
                            <th class="border-0">Course</th>
                            <th class="border-0">Submitted Date</th>
                            <th class="border-0">Status</th>
                            <th class="border-0">Marks</th>
                            <th class="border-0 text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($submissions) && count($submissions) > 0)
                            @foreach($submissions as $index => $submission)
                                <tr>
                                    <td class="ps-3">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm bg-primary bg-opacity-10 rounded-circle me-2">
                                                <span class="material-symbols-outlined text-primary" style="font-size: 1.1rem;">person</span>
                                            </div>
                                            <strong>{{ $submission->student->user->name ?? 'N/A' }}</strong>
                                        </div>
                                    </td>
                                    <td>{{ $submission->assignment->title ?? 'N/A' }}</td>
                                    <td>{{ $submission->assignment->course->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="material-symbols-outlined me-1 text-muted" style="font-size: 1rem;">calendar_today</span>
                                        {{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td>
                                        @if($submission->status === 'reviewed')
                                            <span class="badge bg-success">Reviewed</span>
                                        @elseif($submission->status === 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($submission->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($submission->marks !== null)
                                            <strong class="text-success">{{ $submission->marks }} / {{ $submission->assignment->max_marks ?? 'N/A' }}</strong>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('teacher.evaluate-submission', $submission->id) }}" class="btn btn-sm btn-warning">
                                            <span class="material-symbols-outlined me-1" style="font-size: 1rem;">grade</span>
                                            Evaluate
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <span class="material-symbols-outlined text-muted" style="font-size: 3rem;">inbox</span>
                                    <p class="text-muted mt-2 mb-0">No submissions found.</p>
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