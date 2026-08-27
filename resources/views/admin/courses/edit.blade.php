@extends('layouts.admin')

@section('title', 'Edit Course')

@section('content')
<form method="POST" action="{{ route('admin.courses.update', $course->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">info</span>
            <h3>Course Information</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="title">Course Name *</label>
                    <input type="text" name="title" id="title" class="form-input" value="{{ old('title', $course->title) }}" required>
                    @error('title') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="category_id">Category *</label>
                    <select name="category_id" id="category_id" class="form-input" required>
                        <option value="">Select Category</option>
                        @foreach($categories ?? [] as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $course->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="short_description">Short Description</label>
                <input type="text" name="short_description" id="short_description" class="form-input" value="{{ old('short_description', $course->short_description) }}">
                @error('short_description') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="description">Full Description</label>
                <textarea name="description" id="description" class="form-input" rows="5">{{ old('description', $course->description) }}</textarea>
                @error('description') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">settings</span>
            <h3>Details & Pricing</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-3">
                <div class="form-group">
                    <label class="form-label" for="duration">Duration</label>
                    <input type="text" name="duration" id="duration" class="form-input" value="{{ old('duration', $course->duration) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="eligibility">Eligibility</label>
                    <input type="text" name="eligibility" id="eligibility" class="form-input" value="{{ old('eligibility', $course->eligibility) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="fees">Fees (₹)</label>
                    <input type="number" name="fees" id="fees" class="form-input" value="{{ old('fees', $course->fees) }}">
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="batch_information">Batch Timings</label>
                    <input type="text" name="batch_information" id="batch_information" class="form-input" value="{{ old('batch_information', $course->batch_information) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="career_information">Career Opportunities</label>
                    <input type="text" name="career_information" id="career_information" class="form-input" value="{{ old('career_information', $course->career_information) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="overview">Course Overview</label>
                <textarea name="overview" id="overview" class="form-input" rows="3">{{ old('overview', $course->overview) }}</textarea>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">image</span>
            <h3>Media & Status</h3>
        </div>
        <div class="card-body">
            @if($course->image)
                <div style="margin-bottom:16px;">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='80'%3E%3Crect fill='%23f1f3f5' width='120' height='80'/%3E%3C/svg%3E" data-src="{{ asset('storage/' . $course->image) }}" alt="{{ $course->title }}" loading="lazy" decoding="async" class="lazy-img" style="width:120px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--gray-200);">
                </div>
            @endif
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="image">Course Image</label>
                    <div class="file-input-wrap">
                        <input type="file" name="image" id="image" class="form-input" accept="image/*">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-input">
                        <option value="published" {{ old('status', $course->status) === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ old('status', $course->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Course
        </button>
        <a href="{{ route('admin.courses.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection
