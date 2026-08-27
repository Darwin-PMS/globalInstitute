@extends('layouts.frontend')

@section('title', 'My Assignments')

@section('content')
<section class="section">
    <div class="container">

        <div class="section-header">
            <h3>My Assignments</h3>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Course</th>
                        <th>Due Date</th>
                        <th>Max Marks</th>
                        <th>Status</th>
                        <th>Marks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments ?? [] as $assignment)
                        @php
                            $submission = $assignment->submission ?? null;
                            $status = 'Not Submitted';
                            $statusClass = 'not-submitted';
                            if ($submission) {
                                if ($submission->marks_obtained !== null) {
                                    $status = 'Evaluated';
                                    $statusClass = 'evaluated';
                                } else {
                                    $status = 'Submitted';
                                    $statusClass = 'submitted';
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $assignment->title }}</td>
                            <td>{{ $assignment->course->title ?? 'N/A' }}</td>
                            <td>{{ $assignment->due_date ? \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $assignment->max_marks ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $statusClass }}">{{ $status }}</span>
                            </td>
                            <td>
                                @if($submission && $submission->marks_obtained !== null)
                                    {{ $submission->marks_obtained }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if(!$submission)
                                    <a href="{{ route('student.submit-assignment', $assignment->id) }}" class="btn btn-primary btn-sm">
                                        <span class="material-symbols-outlined">upload</span> Submit
                                    </a>
                                @elseif($submission)
                                    <a href="{{ route('student.view-assignment', $assignment->id) }}" class="btn btn-outline btn-sm">
                                        <span class="material-symbols-outlined">visibility</span> View
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No assignments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</section>
@endsection
