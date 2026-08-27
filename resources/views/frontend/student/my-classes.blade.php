@extends('layouts.frontend')

@section('title', 'My Classes')

@section('content')
<section class="section">
    <div class="container">

        <div class="section-header">
            <h3>My Classes</h3>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Course</th>
                        <th>Batch</th>
                        <th>Teacher</th>
                        <th>Provider</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classes ?? [] as $class)
                        <tr>
                            <td>{{ $class->date ? \Carbon\Carbon::parse($class->date)->format('M d, Y') : 'N/A' }}</td>
                            <td>{{ $class->start_time }} - {{ $class->end_time }}</td>
                            <td>{{ $class->course->title ?? 'N/A' }}</td>
                            <td>{{ $class->batch->name ?? 'N/A' }}</td>
                            <td>{{ $class->teacher->name ?? 'N/A' }}</td>
                            <td>{{ $class->meeting_provider ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ strtolower($class->status ?? 'pending') }}">
                                    {{ $class->status ?? 'Pending' }}
                                </span>
                            </td>
                            <td>
                                @if(in_array(strtolower($class->status ?? ''), ['live', 'scheduled', 'upcoming']))
                                    <a href="{{ $class->meeting_url ?? '#' }}" class="btn btn-primary btn-sm" target="_blank">
                                        <span class="material-symbols-outlined">videocam</span> Join
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No classes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</section>
@endsection
