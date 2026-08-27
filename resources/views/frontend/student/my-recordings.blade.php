@extends('layouts.frontend')

@section('title', 'My Recordings')

@section('content')
<section class="section">
    <div class="container">

        <div class="section-header">
            <h3>My Recordings</h3>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Class</th>
                        <th>Course</th>
                        <th>Duration</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recordings ?? [] as $recording)
                        <tr>
                            <td>{{ $recording->title }}</td>
                            <td>{{ $recording->class->title ?? 'N/A' }}</td>
                            <td>{{ $recording->class->course->title ?? 'N/A' }}</td>
                            <td>{{ $recording->duration ?? 'N/A' }}</td>
                            <td>{{ $recording->created_at ? $recording->created_at->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                <a href="{{ $recording->url ?? '#' }}" class="btn btn-primary btn-sm" target="_blank">
                                    <span class="material-symbols-outlined">play_circle</span> Watch
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No recordings found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</section>
@endsection
