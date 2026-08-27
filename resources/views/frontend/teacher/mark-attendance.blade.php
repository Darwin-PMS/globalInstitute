@extends('layouts.frontend')

@section('title', 'Mark Attendance')

@section('content')
<div class="container-fluid py-4">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="mb-1">Mark Attendance</h3>
            <p class="text-muted mb-0">Record attendance for your class session.</p>
        </div>
        <a href="{{ route('teacher.my-classes') }}" class="btn btn-outline-secondary">
            <span class="material-symbols-outlined me-1" style="font-size: 1rem;">arrow_back</span>
            Back to Classes
        </a>
    </div>

    <!-- Class Info Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <small class="text-muted d-block">Class Title</small>
                    <strong>{{ $class->title ?? 'N/A' }}</strong>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Date</small>
                    <span class="material-symbols-outlined me-1 text-muted" style="font-size: 1rem;">calendar_today</span>
                    {{ $class->date ? \Carbon\Carbon::parse($class->date)->format('M d, Y') : 'N/A' }}
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Course</small>
                    <span>{{ $class->course->name ?? 'N/A' }}</span>
                </div>
                <div class="col-md-3">
                    <small class="text-muted d-block">Batch</small>
                    <span>{{ $class->batch->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Form -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <span class="material-symbols-outlined me-2 align-middle">people</span>
                Student Attendance
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('teacher.mark-attendance', $class->id) }}" method="POST">
                @csrf
                @method('POST')

                @if(isset($students) && count($students) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 ps-3" style="width: 50px;">#</th>
                                    <th class="border-0">Student Name</th>
                                    <th class="border-0 text-center" style="width: 100px;">Present</th>
                                    <th class="border-0 text-center" style="width: 100px;">Absent</th>
                                    <th class="border-0 text-center" style="width: 100px;">Late</th>
                                    <th class="border-0 text-center" style="width: 100px;">Excused</th>
                                    <th class="border-0" style="width: 250px;">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $index => $student)
                                    <tr>
                                        <td class="ps-3">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm bg-primary bg-opacity-10 rounded-circle me-2">
                                                    <span class="material-symbols-outlined text-primary" style="font-size: 1.1rem;">person</span>
                                                </div>
                                                <div>
                                                    <strong>{{ $student->user->name ?? 'N/A' }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $student->roll_number ?? '' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-check-inline m-0">
                                                <input class="form-check-input" type="radio" name="attendance[{{ $index }}][status]" value="present" id="present_{{ $student->id }}">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-check-inline m-0">
                                                <input class="form-check-input" type="radio" name="attendance[{{ $index }}][status]" value="absent" id="absent_{{ $student->id }}">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-check-inline m-0">
                                                <input class="form-check-input" type="radio" name="attendance[{{ $index }}][status]" value="late" id="late_{{ $student->id }}">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-check-inline m-0">
                                                <input class="form-check-input" type="radio" name="attendance[{{ $index }}][status]" value="excused" id="excused_{{ $student->id }}">
                                            </div>
                                        </td>
                                        <td>
                                            <input type="hidden" name="attendance[{{ $index }}][student_id]" value="{{ $student->id }}">
                                            <input type="text" class="form-control form-control-sm" name="attendance[{{ $index }}][notes]" placeholder="Optional notes">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary px-4">
                            <span class="material-symbols-outlined me-1" style="font-size: 1.1rem;">save</span>
                            Save Attendance
                        </button>
                    </div>
                @else
                    <div class="text-center py-5">
                        <span class="material-symbols-outlined text-muted" style="font-size: 3rem;">people</span>
                        <p class="text-muted mt-2 mb-0">No students enrolled in this class.</p>
                    </div>
                @endif
            </form>
        </div>
    </div>

</div>
@endsection