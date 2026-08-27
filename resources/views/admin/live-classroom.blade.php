@extends('layouts.admin')

@section('pageTitle', 'Live Classroom · Global Institute Admin')

@section('content')
<div style="padding:24px;">
    <div style="margin-bottom:24px;">
        <h2 style="font-size:20px;font-weight:700;color:var(--color-heading,#f1f5f9);margin:0 0 4px;">Live Classroom</h2>
        <p style="font-size:13px;color:var(--color-muted,#94a3b8);margin:0;">Join and manage live class sessions with real-time video, screen sharing, and chat.</p>
    </div>

    @php
        $liveClasses = $classes->filter(fn($c) => $c['status'] === 'live');
        $scheduledClasses = $classes->filter(fn($c) => in_array($c['status'], ['scheduled', 'starting_soon']));
        $completedClasses = $classes->filter(fn($c) => $c['status'] === 'completed');
    @endphp

    {{-- Live Now --}}
    @if($liveClasses->count() > 0)
    <div style="margin-bottom:32px;">
        <h3 style="font-size:14px;font-weight:600;color:var(--color-heading,#f1f5f9);margin:0 0 12px;display:flex;align-items:center;gap:8px;">
            <span style="width:8px;height:8px;border-radius:50%;background:#10b981;animation:pulse 1.5s infinite;"></span>
            Live Now
            <span style="font-size:11px;color:var(--color-muted,#94a3b8);font-weight:400;">({{ $liveClasses->count() }})</span>
        </h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;">
            @foreach($liveClasses as $class)
            <div style="background:var(--color-card,#0f172a);border:1px solid #10b981;border-radius:16px;padding:20px;box-shadow:0 0 20px rgba(16,185,129,0.1);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;background:#059669;color:#ecfdf5;">
                        <span style="width:6px;height:6px;border-radius:50%;background:currentColor;animation:pulse 1.5s infinite;"></span>
                        LIVE
                    </span>
                    <span style="font-size:11px;color:var(--color-muted,#64748b);">{{ \Carbon\Carbon::parse($class['class_date'])->format('d M Y') }}</span>
                </div>
                <h4 style="font-size:16px;font-weight:700;color:var(--color-heading,#f1f5f9);margin:0 0 6px;">{{ $class['title'] }}</h4>
                <div style="font-size:12px;color:var(--color-muted,#94a3b8);margin-bottom:4px;">
                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">schedule</span>
                    {{ $class['start_time'] }}{{ $class['end_time'] ? ' – '.$class['end_time'] : '' }}
                </div>
                @if($class['course'])
                <div style="font-size:12px;color:var(--color-muted,#94a3b8);margin-bottom:4px;">
                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">school</span>
                    {{ $class['course']['title'] }}
                </div>
                @endif
                @if($class['teacher'])
                <div style="font-size:12px;color:var(--color-muted,#94a3b8);margin-bottom:16px;">
                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">person</span>
                    {{ $class['teacher']['name'] }}
                </div>
                @endif
                <a href="{{ url('/learning/room/resolve/'.$class['id']) }}" target="_blank"
                   style="display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:10px;border-radius:10px;background:#059669;color:white;text-decoration:none;font-size:13px;font-weight:600;transition:all 0.15s;">
                    <span class="material-symbols-outlined" style="font-size:18px;">videocam</span>
                    Join Now
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Scheduled --}}
    <div style="margin-bottom:32px;">
        <h3 style="font-size:14px;font-weight:600;color:var(--color-heading,#f1f5f9);margin:0 0 12px;">
            Scheduled Classes
            <span style="font-size:11px;color:var(--color-muted,#94a3b8);font-weight:400;">({{ $scheduledClasses->count() }})</span>
        </h3>

        @if($scheduledClasses->count() === 0)
        <div style="text-align:center;padding:40px 20px;background:var(--color-card,#0f172a);border:1px solid var(--color-border,#1e293b);border-radius:16px;">
            <span class="material-symbols-outlined" style="font-size:40px;color:var(--color-muted,#475569);">videocam_off</span>
            <p style="color:var(--color-muted,#94a3b8);margin:12px 0 0;font-size:13px;">No scheduled classes.</p>
            <a href="{{ route('admin.online-classes.create') }}" style="display:inline-block;margin-top:12px;padding:8px 16px;border-radius:8px;background:#6366f1;color:white;text-decoration:none;font-size:13px;">Schedule a Class</a>
        </div>
        @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;">
            @foreach($scheduledClasses as $class)
            <div style="background:var(--color-card,#0f172a);border:1px solid var(--color-border,#1e293b);border-radius:16px;padding:20px;transition:all 0.2s;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $class['status'] === 'starting_soon' ? '#d97706' : '#1e40af' }};color:{{ $class['status'] === 'starting_soon' ? '#fef3c7' : '#dbeafe' }};">
                        @if($class['status'] === 'starting_soon')
                        <span style="width:6px;height:6px;border-radius:50%;background:currentColor;animation:pulse 1.5s infinite;"></span>
                        @endif
                        {{ $class['status'] === 'starting_soon' ? 'Starting Soon' : 'Scheduled' }}
                    </span>
                    <span style="font-size:11px;color:var(--color-muted,#64748b);">{{ \Carbon\Carbon::parse($class['class_date'])->format('d M Y') }}</span>
                </div>
                <h4 style="font-size:16px;font-weight:700;color:var(--color-heading,#f1f5f9);margin:0 0 6px;">{{ $class['title'] }}</h4>
                <div style="font-size:12px;color:var(--color-muted,#94a3b8);margin-bottom:4px;">
                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">schedule</span>
                    {{ $class['start_time'] }}{{ $class['end_time'] ? ' – '.$class['end_time'] : '' }}
                </div>
                @if($class['course'])
                <div style="font-size:12px;color:var(--color-muted,#94a3b8);margin-bottom:4px;">
                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">school</span>
                    {{ $class['course']['title'] }}
                </div>
                @endif
                @if($class['teacher'])
                <div style="font-size:12px;color:var(--color-muted,#94a3b8);margin-bottom:16px;">
                    <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;">person</span>
                    {{ $class['teacher']['name'] }}
                </div>
                @endif
                <div style="display:flex;gap:8px;">
                    <a href="{{ url('/learning/room/resolve/'.$class['id']) }}" target="_blank"
                       style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;border-radius:10px;background:#6366f1;color:white;text-decoration:none;font-size:13px;font-weight:600;">
                        <span class="material-symbols-outlined" style="font-size:18px;">login</span>
                        Enter Lobby
                    </a>
                    <a href="{{ route('admin.online-classes.edit', $class['id']) }}"
                       style="padding:10px 14px;border-radius:10px;border:1px solid var(--color-border,#334155);background:transparent;color:var(--color-muted,#94a3b8);text-decoration:none;display:flex;align-items:center;justify-content:center;">
                        <span class="material-symbols-outlined" style="font-size:18px;">edit</span>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Completed --}}
    @if($completedClasses->count() > 0)
    <div>
        <h3 style="font-size:14px;font-weight:600;color:var(--color-heading,#f1f5f9);margin:0 0 12px;">
            Completed
            <span style="font-size:11px;color:var(--color-muted,#94a3b8);font-weight:400;">({{ $completedClasses->count() }})</span>
        </h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;">
            @foreach($completedClasses->take(6) as $class)
            <div style="background:var(--color-card,#0f172a);border:1px solid var(--color-border,#1e293b);border-radius:16px;padding:20px;opacity:0.7;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                    <span style="padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;background:#475569;color:#e2e8f0;">Completed</span>
                    <span style="font-size:11px;color:var(--color-muted,#64748b);">{{ \Carbon\Carbon::parse($class['class_date'])->format('d M Y') }}</span>
                </div>
                <h4 style="font-size:14px;font-weight:600;color:var(--color-heading,#f1f5f9);margin:0 0 4px;">{{ $class['title'] }}</h4>
                @if($class['course'])
                <div style="font-size:12px;color:var(--color-muted,#94a3b8);">{{ $class['course']['title'] }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}
</style>
@endsection
