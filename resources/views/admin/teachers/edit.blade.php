@extends('layouts.admin')

@section('title', 'Edit Teacher')

@section('content')
<form method="POST" action="{{ route('admin.teachers.update', $teacher->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">person</span>
            <h3>Personal Information</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name *</label>
                    <input type="text" name="name" id="name" class="form-input" value="{{ old('name', $teacher->name) }}" required>
                    @error('name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Phone *</label>
                    <input type="text" name="phone" id="phone" class="form-input" value="{{ old('phone', $teacher->phone) }}" required>
                    @error('phone') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-input" value="{{ old('email', $teacher->email) }}">
                    @error('email') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="qualification">Qualification</label>
                    <input type="text" name="qualification" id="qualification" class="form-input" value="{{ old('qualification', $teacher->qualification) }}">
                    @error('qualification') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="specialization">Specialization</label>
                    <input type="text" name="specialization" id="specialization" class="form-input" value="{{ old('specialization', $teacher->specialization) }}">
                    @error('specialization') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="experience">Experience</label>
                    <input type="text" name="experience" id="experience" class="form-input" value="{{ old('experience', $teacher->experience) }}">
                    @error('experience') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">work</span>
            <h3>Employment Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label" for="employment_type">Employment Type *</label>
                    <select name="employment_type" id="employment_type" class="form-input" required>
                        <option value="">Select Type</option>
                        <option value="full_time" {{ old('employment_type', $teacher->employment_type) === 'full_time' ? 'selected' : '' }}>Full Time</option>
                        <option value="part_time" {{ old('employment_type', $teacher->employment_type) === 'part_time' ? 'selected' : '' }}>Part Time</option>
                        <option value="contract" {{ old('employment_type', $teacher->employment_type) === 'contract' ? 'selected' : '' }}>Contract</option>
                        <option value="visiting" {{ old('employment_type', $teacher->employment_type) === 'visiting' ? 'selected' : '' }}>Visiting</option>
                    </select>
                    @error('employment_type') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="joining_date">Joining Date</label>
                    <input type="date" name="joining_date" id="joining_date" class="form-input" value="{{ old('joining_date', $teacher->joining_date ? $teacher->joining_date->format('Y-m-d') : '') }}">
                    @error('joining_date') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-input">
                        <option value="active" {{ old('status', $teacher->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $teacher->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">description</span>
            <h3>Additional Information</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="bio">Bio</label>
                <textarea name="bio" id="bio" class="form-input" rows="3">{{ old('bio', $teacher->bio) }}</textarea>
                @error('bio') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="notes">Notes</label>
                <textarea name="notes" id="notes" class="form-input" rows="3">{{ old('notes', $teacher->notes) }}</textarea>
                @error('notes') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Teacher
        </button>
        <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection
