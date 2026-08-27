@extends('layouts.admin')

@section('title', 'Add Gallery Image')

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">
        <span class="material-symbols-outlined" style="color:var(--primary);">photo_library</span>
        <h3>Upload Image</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.gallery.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">Title *</label>
                <input type="text" name="title" class="form-input" value="{{ old('title') }}" required placeholder="Image title">
                @error('title') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="3" placeholder="Brief description...">{{ old('description') }}</textarea>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-input">
                        <option value="">No category</option>
                        @foreach($categories ?? [] as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Image File *</label>
                <div class="file-input-wrap">
                    <input type="file" name="image" class="form-input" accept="image/*" required>
                </div>
                @error('image') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined" style="font-size:18px;">upload</span> Upload Image</button>
                <a href="{{ route('admin.gallery.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
