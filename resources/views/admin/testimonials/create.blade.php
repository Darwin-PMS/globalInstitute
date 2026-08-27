@extends('layouts.admin')

@section('title', 'Add Testimonial')

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">
        <span class="material-symbols-outlined" style="color:var(--primary);">reviews</span>
        <h3>New Testimonial</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.testimonials.store') }}">
            @csrf
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Student Name *</label>
                    <input type="text" name="student_name" class="form-input" value="{{ old('student_name') }}" required placeholder="Full name">
                    @error('student_name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Course</label>
                    <input type="text" name="course" class="form-input" value="{{ old('course') }}" placeholder="e.g. AutoCAD Fundamentals">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Testimonial *</label>
                <textarea name="testimonial" class="form-input" rows="4" required placeholder="What the student said...">{{ old('testimonial') }}</textarea>
                @error('testimonial') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Batch</label>
                    <input type="text" name="batch" class="form-input" value="{{ old('batch') }}" placeholder="e.g. Jan 2026">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined" style="font-size:18px;">save</span> Create Testimonial</button>
                <a href="{{ route('admin.testimonials.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
