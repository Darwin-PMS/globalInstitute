@extends('layouts.frontend')

@section('title', 'My Classes')

@section('content')
<div class="container-fluid py-4">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="mb-1">My Classes</h3>
            <p class="text-muted mb-0">All classes assigned to you.</p>
        </div>
        <a href="{{ route('teacher.dashboard') }}" class="btn btn-outline-secondary">
            <span class="material-symbols-outlined me-1" style="font-size: 1rem;">arrow_back</span>
            Dashboard
        </a>
    </div>

    <!-- Classes Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 ps-3">#</th>
                            <th class="border-0">Date</th>
                            <th class="border-0">Time</th>
                            <th class="border-0">Course</th>
                            <th class="border-0">Batch</th>
                            <th class="border-0">Title</th>
                            <th class="border-0">Status</th>
                            <th class="border-0 text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($classes) && count($classes) > 0)
                            @foreach($classes as $index => $class)
                                <tr>
                                    <td class="ps-3">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="material-symbols-outlined me-1 text-muted" style="font-size: 1rem;">calendar_today</span>
                                        {{ $class->date ? \Carbon\Carbon::parse($class->date)->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td>
                                        <span class="material-symbols-outlined me-1 text-muted" style="font-size: 1rem;">schedule</span>
                                        {{ $class->start_time }} - {{ $class->end_time }}
                                    </td>
                                    <td>{{ $class->course->name ?? 'N/A' }}</td>
                                    <td>{{ $class->batch->name ?? 'N/A' }}</td>
                                    <td>
                                        <strong>{{ $class->title }}</strong>
                                    </td>
                                    <td>
                                        @if($class->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($class->status === 'ongoing')
                                            <span class="badge bg-primary">Ongoing</span>
                                        @else
                                            <span class="badge bg-secondary">Upcoming</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex gap-1 justify-content-end">
                                            @if($class->status === 'completed')
                                                <a href="{{ route('teacher.mark-attendance', $class->id) }}" class="btn btn-sm btn-outline-primary" title="Mark Attendance">
                                                    <span class="material-symbols-outlined" style="font-size: 1rem;">fact_check</span>
                                                </a>
                                            @endif
                                            @if($class->meeting_link)
                                                <a href="{{ $class->meeting_link }}" target="_blank" class="btn btn-sm btn-outline-success" title="Meeting Link">
                                                    <span class="material-symbols-outlined" style="font-size: 1rem;">videocam</span>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <span class="material-symbols-outlined text-muted" style="font-size: 3rem;">class</span>
                                    <p class="text-muted mt-2 mb-0">No classes assigned yet.</p>
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