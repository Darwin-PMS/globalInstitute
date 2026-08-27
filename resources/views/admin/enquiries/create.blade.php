@extends('layouts.admin')

@section('title', 'Add Enquiry')

@section('content')
<form method="POST" action="{{ route('admin.enquiries.store') }}">
    @csrf
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">person</span>
            <h3>Contact Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-input" value="{{ old('name') }}" required placeholder="Full name">
                    @error('name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Phone *</label>
                    <input type="tel" name="phone" class="form-input" value="{{ old('phone') }}" required placeholder="+91 00000 00000">
                    @error('phone') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="{{ old('email') }}" placeholder="email@example.com">
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
                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Source</label>
                    <select name="source" class="form-input">
                        <option value="">Select source...</option>
                        <option value="website" {{ old('source') === 'website' ? 'selected' : '' }}>Website</option>
                        <option value="walk_in" {{ old('source') === 'walk_in' ? 'selected' : '' }}>Walk-in</option>
                        <option value="referral" {{ old('source') === 'referral' ? 'selected' : '' }}>Referral</option>
                        <option value="phone" {{ old('source') === 'phone' ? 'selected' : '' }}>Phone Call</option>
                        <option value="social_media" {{ old('source') === 'social_media' ? 'selected' : '' }}>Social Media</option>
                        <option value="other" {{ old('source') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-input" value="{{ old('subject') }}" placeholder="Quick subject line">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-input" rows="3" placeholder="Enquiry details...">{{ old('message') }}</textarea>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined" style="font-size:18px;">save</span> Create Enquiry</button>
        <a href="{{ route('admin.enquiries.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection
