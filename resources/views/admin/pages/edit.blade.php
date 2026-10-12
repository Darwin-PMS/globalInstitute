@extends('layouts.admin')

@section('title', 'Edit Page')

@section('actions')
    <a href="{{ route('admin.pages.index') }}" class="btn btn-outline btn-sm">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back to Pages
    </a>
@endsection

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <h1>Edit Page</h1>
        <p class="page-header-desc">Update the content, visibility and SEO metadata for this page.</p>
    </div>
    <div class="page-header-actions">
        @if($page->status === 'published')
            <a href="{{ url('/' . $page->slug) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                <span class="material-symbols-outlined" style="font-size:18px;">open_in_new</span> View live
            </a>
        @endif
        <span class="badge {{ $page->status === 'published' ? 'badge-success' : 'badge-draft' }}">
            {{ ucfirst($page->status) }}
        </span>
    </div>
</div>

<div class="card" style="max-width:900px;overflow:visible;">
    <div class="card-header">
        <span class="material-symbols-outlined" style="color:var(--primary);">description</span>
        <h3>{{ $page->title }}</h3>
        <code class="pages-slug" style="margin-left:auto;">/{{ $page->slug }}</code>
    </div>
    <form method="POST" action="{{ route('admin.pages.update', $page->id) }}">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Title <span class="req-mark">*</span></label>
                <input type="text" name="title" class="form-input" value="{{ old('title', $page->title) }}" required>
                @error('title') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Content</label>
                <textarea name="content" class="form-input" rows="10" data-rich-editor>{{ old('content', $page->content) }}</textarea>
                @error('content') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Excerpt</label>
                    <textarea name="excerpt" class="form-input" rows="2">{{ old('excerpt', $page->excerpt) }}</textarea>
                    @error('excerpt') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Featured Image URL</label>
                    <input type="text" name="featured_image" class="form-input" value="{{ old('featured_image', $page->featured_image) }}" placeholder="https://...">
                    @error('featured_image') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Template</label>
                    <input type="text" name="template" class="form-input" value="{{ old('template', $page->template) }}" placeholder="default">
                    @error('template') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="published" {{ old('status', $page->status) === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ old('status', $page->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                    @error('status') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">SEO Title</label>
                    <input type="text" name="seo_title" class="form-input" value="{{ old('seo_title', $page->seo_title) }}">
                    @error('seo_title') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">SEO Description</label>
                    <textarea name="seo_description" class="form-input" rows="2">{{ old('seo_description', $page->seo_description) }}</textarea>
                    @error('seo_description') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
        <div class="form-actionbar">
            <a href="{{ route('admin.pages.index') }}" class="btn btn-outline btn-sm">Cancel</a>
            <button type="submit" class="btn btn-primary btn-sm">
                <span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Page
            </button>
        </div>
    </form>
</div>
@endsection
