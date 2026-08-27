@extends('layouts.admin')

@section('title', 'Add Room')

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">
        <span class="material-symbols-outlined" style="color:var(--primary);">meeting_room</span>
        <h3>New Room</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.rooms.store') }}">
            @csrf
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="name">Room Name *</label>
                    <input type="text" name="name" id="name" class="form-input" value="{{ old('name') }}" required placeholder="e.g. Lab 101">
                    @error('name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="room_number">Room Number</label>
                    <input type="text" name="room_number" id="room_number" class="form-input" value="{{ old('room_number') }}" placeholder="e.g. 101">
                    @error('room_number') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="capacity">Capacity</label>
                    <input type="number" name="capacity" id="capacity" class="form-input" value="{{ old('capacity') }}" placeholder="e.g. 30">
                    @error('capacity') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="type">Type</label>
                    <select name="type" id="type" class="form-input">
                        <option value="classroom" {{ old('type', 'classroom') === 'classroom' ? 'selected' : '' }}>Classroom</option>
                        <option value="lab" {{ old('type') === 'lab' ? 'selected' : '' }}>Lab</option>
                        <option value="seminar" {{ old('type') === 'seminar' ? 'selected' : '' }}>Seminar Hall</option>
                        <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-input">
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined" style="font-size:18px;">save</span> Create Room</button>
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
