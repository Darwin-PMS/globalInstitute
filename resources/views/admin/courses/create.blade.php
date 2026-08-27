@extends('layouts.admin')

@section('title', 'Add Course')

@section('content')
<form method="POST" action="{{ route('admin.courses.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">info</span>
            <h3>Course Information</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="title">Course Name *</label>
                    <input type="text" name="title" id="title" class="form-input" value="{{ old('title') }}" required placeholder="e.g. AutoCAD Masterclass">
                    @error('title')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="category_id">Category *</label>
                    <select name="category_id" id="category_id" class="form-input" required>
                        <option value="">Select Category</option>
                        @foreach($categories ?? [] as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <span class="form-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="short_description">Short Description</label>
                <input type="text" name="short_description" id="short_description" class="form-input" value="{{ old('short_description') }}" placeholder="Brief description shown on course cards">
                @error('short_description')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Full Description</label>
                <textarea name="description" id="description" class="form-input" rows="5" placeholder="Detailed course description...">{{ old('description') }}</textarea>
                @error('description')
                    <span class="form-error">{{ $message }}</span>
                @enderror
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
                    <input type="text" name="duration" id="duration" class="form-input" value="{{ old('duration') }}" placeholder="e.g. 3 Months">
                </div>
                <div class="form-group">
                    <label class="form-label" for="eligibility">Eligibility</label>
                    <input type="text" name="eligibility" id="eligibility" class="form-input" value="{{ old('eligibility') }}" placeholder="e.g. 10th Pass">
                </div>
                <div class="form-group">
                    <label class="form-label" for="fees">Fees (₹)</label>
                    <input type="number" name="fees" id="fees" class="form-input" value="{{ old('fees') }}" placeholder="e.g. 8000">
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="batch_information">Batch Timings</label>
                    <input type="text" name="batch_information" id="batch_information" class="form-input" value="{{ old('batch_information') }}" placeholder="e.g. Morning: 9-11 AM, Evening: 6-8 PM">
                </div>
                <div class="form-group">
                    <label class="form-label" for="career_information">Career Opportunities</label>
                    <input type="text" name="career_information" id="career_information" class="form-input" value="{{ old('career_information') }}" placeholder="e.g. CAD Drafter, Design Engineer">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="overview">Course Overview</label>
                <textarea name="overview" id="overview" class="form-input" rows="3" placeholder="Overview paragraph for the course detail page...">{{ old('overview') }}</textarea>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">image</span>
            <h3>Media & Status</h3>
        </div>
        <div class="card-body">
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
                        <option value="published" {{ old('status', 'published') === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Create Course
        </button>
        <a href="{{ route('admin.courses.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection
