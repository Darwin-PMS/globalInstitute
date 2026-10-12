@extends('layouts.frontend')

@section('title', $course->title ?? 'Course Detail')

@section('content')
<section class="section">
    <div class="container">

        <div class="course-header">
            <div class="course-header-info">
                <span class="badge badge-category">{{ $course->category->name ?? 'N/A' }}</span>
                <h2>{{ $course->title }}</h2>
            </div>
        </div>

        <div class="tabs-nav">
            <button class="tab-btn active" data-tab="lessons">
                <span class="material-symbols-outlined">play_lesson</span> Lessons
            </button>
            <button class="tab-btn" data-tab="classes">
                <span class="material-symbols-outlined">class</span> Classes
            </button>
            <button class="tab-btn" data-tab="assignments">
                <span class="material-symbols-outlined">assignment</span> Assignments
            </button>
            <button class="tab-btn" data-tab="materials">
                <span class="material-symbols-outlined">folder</span> Materials
            </button>
        </div>

        <!-- Lessons Tab -->
        <div class="tab-content active" id="tab-lessons">
            @forelse($course->modules ?? [] as $moduleIndex => $module)
                <div class="accordion">
                    <button class="accordion-header">
                        <span>Module {{ $moduleIndex + 1 }}: {{ $module->name }}</span>
                        <span class="material-symbols-outlined accordion-icon">expand_more</span>
                    </button>
                    <div class="accordion-body">
                        @forelse($module->lessons ?? [] as $lesson)
                            <div class="lesson-item {{ ($lesson->isCompleted ?? false) ? 'completed' : '' }}">
                                <div class="lesson-info">
                                    @if($lesson->isCompleted ?? false)
                                        <span class="material-symbols-outlined lesson-status completed">check_circle</span>
                                    @else
                                        <span class="material-symbols-outlined lesson-status">radio_button_unchecked</span>
                                    @endif
                                    <span class="lesson-title">{{ $lesson->title }}</span>
                                    @if($lesson->is_free ?? false)
                                        <span class="badge badge-free">Free</span>
                                    @endif
                                </div>
                                <span class="lesson-duration">
                                    <span class="material-symbols-outlined">schedule</span>
                                    {{ $lesson->duration ?? 'N/A' }}
                                </span>
                            </div>
                        @empty
                            <p class="text-muted">No lessons in this module.</p>
                        @endforelse
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <span class="material-symbols-outlined">play_lesson</span>
                    <p>No modules available for this course yet.</p>
                </div>
            @endforelse
        </div>

        <!-- Classes Tab -->
        <div class="tab-content" id="tab-classes">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Teacher</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($course->classes ?? [] as $class)
                            <tr>
                                <td>{{ $class->date ? \Carbon\Carbon::parse($class->date)->format('M d, Y') : 'N/A' }}</td>
                                <td>{{ $class->start_time }} - {{ $class->end_time }}</td>
                                <td>{{ $class->teacher->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge badge-{{ strtolower($class->status ?? 'pending') }}">
                                        {{ $class->status ?? 'Pending' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No classes scheduled for this course.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Assignments Tab -->
        <div class="tab-content" id="tab-assignments">
            <div class="cards-grid">
                @forelse($course->assignments ?? [] as $assignment)
                    <div class="card assignment-card">
                        <div class="card-body">
                            <h4 class="card-title">{{ $assignment->title }}</h4>
                            <p class="card-text">
                                <span class="material-symbols-outlined">calendar_today</span>
                                Due: {{ $assignment->due_date ? \Carbon\Carbon::parse($assignment->due_date)->format('M d, Y') : 'N/A' }}
                            </p>
                            <p class="card-text">
                                <span class="material-symbols-outlined">grading</span>
                                Max Marks: {{ $assignment->max_marks ?? 'N/A' }}
                            </p>
                            @php
                                $submission = $assignment->submission ?? null;
                            @endphp
                            @if($submission)
                                <span class="badge badge-submitted">Submitted</span>
                                @if($submission->marks_obtained !== null)
                                    <span class="badge badge-evaluated">Marks: {{ $submission->marks_obtained }}</span>
                                @endif
                            @else
                                <a href="{{ route('student.submit-assignment', $assignment->id) }}" class="btn btn-primary btn-sm">
                                    <span class="material-symbols-outlined">upload</span> Submit
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <span class="material-symbols-outlined">assignment</span>
                        <p>No assignments for this course yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Materials Tab -->
        <div class="tab-content" id="tab-materials">
            <div class="materials-list">
                @forelse($course->materials ?? [] as $material)
                    <div class="material-item">
                        <div class="material-info">
                            @php
                                $ext = pathinfo($material->file_path ?? '', PATHINFO_EXTENSION);
                                $iconMap = [
                                    'pdf' => 'picture_as_pdf',
                                    'doc' => 'description',
                                    'docx' => 'description',
                                    'xls' => 'table_chart',
                                    'xlsx' => 'table_chart',
                                    'ppt' => 'slideshow',
                                    'pptx' => 'slideshow',
                                    'zip' => 'folder_zip',
                                    'rar' => 'folder_zip',
                                ];
                                $icon = $iconMap[strtolower($ext)] ?? 'insert_drive_file';
                            @endphp
                            <span class="material-symbols-outlined material-icon">{{ $icon }}</span>
                            <div>
                                <h5>{{ $material->title }}</h5>
                                <span class="text-muted">{{ strtoupper($ext) }} File</span>
                            </div>
                        </div>
                        <a href="{{ asset('storage/' . $material->file_path) }}" class="btn btn-outline btn-sm" download>
                            <span class="material-symbols-outlined">download</span> Download
                        </a>
                    </div>
                @empty
                    <div class="empty-state">
                        <span class="material-symbols-outlined">folder_off</span>
                        <p>No materials uploaded for this course yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</section>
@endsection
