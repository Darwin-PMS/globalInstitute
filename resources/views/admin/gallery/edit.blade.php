@extends('layouts.admin')

@section('title', 'Edit Gallery Image')

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">
        <span class="material-symbols-outlined" style="color:var(--primary);">photo_library</span>
        <h3>Edit Image</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.gallery.update', $galleryImage->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @if($galleryImage->image)
                <div style="margin-bottom:16px;">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Crect fill='%23f1f3f5' width='200' height='200'/%3E%3C/svg%3E" data-src="{{ asset('storage/' . $galleryImage->image) }}" alt="{{ $galleryImage->title }}" loading="lazy" decoding="async" class="lazy-img" style="max-width:200px;border-radius:8px;border:1px solid var(--gray-200);">
                </div>
            @endif
            <div class="form-group">
                <label class="form-label">Title *</label>
                <input type="text" name="title" class="form-input" value="{{ old('title', $galleryImage->title) }}" required>
                @error('title') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="3">{{ old('description', $galleryImage->description) }}</textarea>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-input">
                        <option value="">No category</option>
                        @foreach($categories ?? [] as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $galleryImage->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="published" {{ old('status', $galleryImage->status) === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ old('status', $galleryImage->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Replace Image</label>
                <div class="file-input-wrap">
                    <input type="file" name="image" class="form-input" accept="image/*">
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Image</button>
                <a href="{{ route('admin.gallery.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
