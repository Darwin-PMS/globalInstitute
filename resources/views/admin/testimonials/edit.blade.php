@extends('layouts.admin')

@section('title', 'Edit Testimonial')

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">
        <span class="material-symbols-outlined" style="color:var(--primary);">reviews</span>
        <h3>Edit Testimonial</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.testimonials.update', $testimonial->id) }}">
            @csrf
            @method('PUT')
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Student Name *</label>
                    <input type="text" name="student_name" class="form-input" value="{{ old('student_name', $testimonial->student_name) }}" required>
                    @error('student_name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Course</label>
                    <input type="text" name="course" class="form-input" value="{{ old('course', $testimonial->course) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Testimonial *</label>
                <textarea name="testimonial" class="form-input" rows="4" required>{{ old('testimonial', $testimonial->testimonial) }}</textarea>
                @error('testimonial') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Batch</label>
                    <input type="text" name="batch" class="form-input" value="{{ old('batch', $testimonial->batch) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="published" {{ old('status', $testimonial->status) === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ old('status', $testimonial->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Testimonial</button>
                <a href="{{ route('admin.testimonials.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
