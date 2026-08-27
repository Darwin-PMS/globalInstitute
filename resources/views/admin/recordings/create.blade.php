@extends('layouts.admin')

@section('title', 'Add Recording')

@section('content')
<form method="POST" action="{{ route('admin.recordings.store') }}">
    @csrf

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">videocam</span>
            <h3>Recording Details</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="online_class_id">Online Class *</label>
                <select name="online_class_id" id="online_class_id" class="form-input" required>
                    <option value="">Select Class</option>
                    @foreach($onlineClasses ?? [] as $class)
                        <option value="{{ $class->id }}" {{ old('online_class_id') == $class->id ? 'selected' : '' }}>{{ $class->title }} - {{ $class->class_date ? \Carbon\Carbon::parse($class->class_date)->format('d M Y') : 'N/A' }}</option>
                    @endforeach
                </select>
                @error('online_class_id') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="title">Title *</label>
                <input type="text" name="title" id="title" class="form-input" value="{{ old('title') }}" required placeholder="Recording title">
                @error('title') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" class="form-input" rows="3" placeholder="Brief description...">{{ old('description') }}</textarea>
                @error('description') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">link</span>
            <h3>Recording Source</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="recording_url">Recording URL *</label>
                <input type="url" name="recording_url" id="recording_url" class="form-input" value="{{ old('recording_url') }}" required placeholder="https://...">
                @error('recording_url') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="duration">Duration</label>
                <input type="text" name="duration" id="duration" class="form-input" value="{{ old('duration') }}" placeholder="e.g. 1:30:00">
                <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Format: H:MM:SS or M:SS</div>
                @error('duration') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">settings</span>
            <h3>Settings</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="visibility">Visibility</label>
                    <select name="visibility" id="visibility" class="form-input">
                        <option value="public" {{ old('visibility', 'public') === 'public' ? 'selected' : '' }}>Public</option>
                        <option value="enrolled" {{ old('visibility') === 'enrolled' ? 'selected' : '' }}>Enrolled Students Only</option>
                        <option value="private" {{ old('visibility') === 'private' ? 'selected' : '' }}>Private</option>
                    </select>
                    @error('visibility') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="is_published">
                        <input type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published', '1') ? 'checked' : '' }} style="margin-right:8px;">
                        Published
                    </label>
                    <div style="font-size:12px;color:var(--gray-500);margin-top:4px;">Make this recording visible to students</div>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Add Recording
        </button>
        <a href="{{ route('admin.recordings.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection
