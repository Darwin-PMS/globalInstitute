@extends('layouts.admin')

@section('title', 'Edit Room')

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">
        <span class="material-symbols-outlined" style="color:var(--primary);">meeting_room</span>
        <h3>Edit Room</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.rooms.update', $room->id) }}">
            @csrf
            @method('PUT')
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="name">Room Name *</label>
                    <input type="text" name="name" id="name" class="form-input" value="{{ old('name', $room->name) }}" required>
                    @error('name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="room_number">Room Number</label>
                    <input type="text" name="room_number" id="room_number" class="form-input" value="{{ old('room_number', $room->room_number) }}">
                    @error('room_number') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="capacity">Capacity</label>
                    <input type="number" name="capacity" id="capacity" class="form-input" value="{{ old('capacity', $room->capacity) }}">
                    @error('capacity') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="type">Type</label>
                    <select name="type" id="type" class="form-input">
                        <option value="classroom" {{ old('type', $room->type) === 'classroom' ? 'selected' : '' }}>Classroom</option>
                        <option value="lab" {{ old('type', $room->type) === 'lab' ? 'selected' : '' }}>Lab</option>
                        <option value="seminar" {{ old('type', $room->type) === 'seminar' ? 'selected' : '' }}>Seminar Hall</option>
                        <option value="other" {{ old('type', $room->type) === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-input">
                    <option value="active" {{ old('status', $room->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $room->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Room</button>
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
