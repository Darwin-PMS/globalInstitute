@extends('layouts.frontend')

@section('title', 'My Courses')

@section('content')
<section class="section">
    <div class="container">

        <div class="section-header">
            <h3>My Courses</h3>
        </div>

        <div class="cards-grid">
            @forelse($enrolledCourses ?? [] as $enrollment)
                <div class="card course-card">
                    <div class="card-img">
                        @if($enrollment->course->image ?? null)
                            <img src="{{ asset('storage/' . $enrollment->course->image) }}" alt="{{ $enrollment->course->title }}">
                        @else
                            <div class="course-img-placeholder gradient-{{ ($enrollment->course->id % 5) + 1 }}">
                                <span class="material-symbols-outlined">menu_book</span>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <span class="badge badge-category">{{ $enrollment->course->category->name ?? 'N/A' }}</span>
                        <h4 class="card-title">{{ $enrollment->course->title }}</h4>
                        <div class="course-meta">
                            <span>
                                <span class="material-symbols-outlined">groups</span>
                                Batch: {{ $enrollment->batch->name ?? 'N/A' }}
                            </span>
                            <span>
                                <span class="material-symbols-outlined">calendar_today</span>
                                Enrolled: {{ $enrollment->created_at ? $enrollment->created_at->format('M d, Y') : 'N/A' }}
                            </span>
                        </div>
                        <div class="progress-wrapper">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: {{ $enrollment->progress ?? 0 }}%"></div>
                            </div>
                            <span class="progress-text">{{ $enrollment->progress ?? 0 }}%</span>
                        </div>
                        <a href="{{ route('student.course-detail', $enrollment->course->id) }}" class="btn btn-primary btn-block">
                            Continue
                        </a>
                    </div>
                </div>
            @empty
                <div class="empty-state full-width">
                    <span class="material-symbols-outlined">school</span>
                    <p>You are not enrolled in any courses yet.</p>
                </div>
            @endforelse
        </div>

    </div>
</section>
@endsection
