@extends('layouts.admin')

@section('title', 'Edit Enquiry')

@section('content')
<form method="POST" action="{{ route('admin.enquiries.update', $enquiry->id) }}">
    @csrf
    @method('PUT')
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">person</span>
            <h3>Contact Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name', $enquiry->name) }}" required>
                    @error('name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Phone *</label>
                    <input type="tel" name="phone" class="form-input" value="{{ old('phone', $enquiry->phone) }}" required>
                    @error('phone') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="{{ old('email', $enquiry->email) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">school</span>
            <h3>Enquiry Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label">Course</label>
                    <select name="course_id" class="form-input">
                        <option value="">Select course...</option>
                        @foreach($courses ?? [] as $course)
                            <option value="{{ $course->id }}" {{ old('course_id', $enquiry->course_id) == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Source</label>
                    <select name="source" class="form-input">
                        <option value="">Select source...</option>
                        @foreach(['website','walk_in','referral','phone','social_media','other'] as $src)
                            <option value="{{ $src }}" {{ old('source', $enquiry->source) === $src ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$src)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-input" required>
                        @foreach(['new','contacted','converted'] as $st)
                            <option value="{{ $st }}" {{ old('status', $enquiry->status) === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-input" rows="3">{{ old('message', $enquiry->message) }}</textarea>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Enquiry</button>
        <a href="{{ route('admin.enquiries.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection
