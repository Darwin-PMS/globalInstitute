@extends('layouts.admin')

@section('title', 'Add Category')

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">
        <span class="material-symbols-outlined" style="color:var(--primary);">folder</span>
        <h3>New Category</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="name">Name *</label>
                <input type="text" name="name" id="name" class="form-input" value="{{ old('name') }}" required placeholder="e.g. CAD/CAM Courses" oninput="document.getElementById('slug').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')">
                @error('name') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="slug">Slug *</label>
                <input type="text" name="slug" id="slug" class="form-input" value="{{ old('slug') }}" required placeholder="auto-generated-from-name">
                @error('slug') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" class="form-input" rows="3" placeholder="Brief description...">{{ old('description') }}</textarea>
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-input">
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined" style="font-size:18px;">save</span> Create Category</button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
