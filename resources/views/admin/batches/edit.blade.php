@extends('layouts.admin')

@section('title', 'Edit Batch')

@section('content')
<form method="POST" action="{{ route('admin.batches.update', $batch->id) }}">
    @csrf
    @method('PUT')

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">groups</span>
            <h3>Batch Information</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="course_id">Course *</label>
                    <select name="course_id" id="course_id" class="form-input" required>
                        <option value="">Select Course</option>
                        @foreach($courses ?? [] as $course)
                            <option value="{{ $course->id }}" {{ old('course_id', $batch->course_id) == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                        @endforeach
                    </select>
                    @error('course_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="batch_name">Batch Name *</label>
                    <input type="text" name="batch_name" id="batch_name" class="form-input" value="{{ old('batch_name', $batch->batch_name) }}" required>
                    @error('batch_name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="batch_code">Batch Code</label>
                    <input type="text" name="batch_code" id="batch_code" class="form-input" value="{{ old('batch_code', $batch->batch_code) }}">
                    @error('batch_code') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="capacity">Capacity</label>
                    <input type="number" name="capacity" id="capacity" class="form-input" value="{{ old('capacity', $batch->capacity) }}">
                    @error('capacity') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">schedule</span>
            <h3>Schedule</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="start_date">Start Date *</label>
                    <input type="date" name="start_date" id="start_date" class="form-input" value="{{ old('start_date', $batch->start_date ? \Carbon\Carbon::parse($batch->start_date)->format('Y-m-d') : '') }}" required>
                    @error('start_date') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="end_date">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-input" value="{{ old('end_date', $batch->end_date ? \Carbon\Carbon::parse($batch->end_date)->format('Y-m-d') : '') }}">
                    @error('end_date') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="start_time">Start Time *</label>
                    <input type="time" name="start_time" id="start_time" class="form-input" value="{{ old('start_time', $batch->start_time ? \Carbon\Carbon::parse($batch->start_time)->format('H:i') : '') }}" required>
                    @error('start_time') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="end_time">End Time *</label>
                    <input type="time" name="end_time" id="end_time" class="form-input" value="{{ old('end_time', $batch->end_time ? \Carbon\Carbon::parse($batch->end_time)->format('H:i') : '') }}" required>
                    @error('end_time') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="days">Days</label>
                <input type="text" name="days" id="days" class="form-input" value="{{ old('days', $batch->days) }}" placeholder="e.g. Mon, Tue, Wed, Thu, Fri">
                @error('days') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">person</span>
            <h3>Trainer & Room</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="trainer_id">Trainer</label>
                    <select name="trainer_id" id="trainer_id" class="form-input">
                        <option value="">Select Trainer</option>
                        @foreach($trainers ?? [] as $trainer)
                            <option value="{{ $trainer->id }}" {{ old('trainer_id', $batch->trainer_id) == $trainer->id ? 'selected' : '' }}>{{ $trainer->name }}</option>
                        @endforeach
                    </select>
                    @error('trainer_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="room_id">Room</label>
                    <select name="room_id" id="room_id" class="form-input">
                        <option value="">Select Room</option>
                        @foreach($rooms ?? [] as $room)
                            <option value="{{ $room->id }}" {{ old('room_id', $batch->room_id) == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
                        @endforeach
                    </select>
                    @error('room_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-input">
                        <option value="upcoming" {{ old('status', $batch->status) === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="active" {{ old('status', $batch->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ old('status', $batch->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ old('status', $batch->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="notes">Notes</label>
                <textarea name="notes" id="notes" class="form-input" rows="3">{{ old('notes', $batch->notes) }}</textarea>
                @error('notes') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Batch
        </button>
        <a href="{{ route('admin.batches.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection
