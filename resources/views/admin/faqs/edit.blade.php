@extends('layouts.admin')

@section('title', 'Edit FAQ')

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">
        <span class="material-symbols-outlined" style="color:var(--primary);">help</span>
        <h3>Edit FAQ</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.faqs.update', $faq->id) }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label">Question *</label>
                <input type="text" name="question" class="form-input" value="{{ old('question', $faq->question) }}" required>
                @error('question') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Answer *</label>
                <textarea name="answer" class="form-input" rows="4" required>{{ old('answer', $faq->answer) }}</textarea>
                @error('answer') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-input" value="{{ old('category', $faq->category) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-input">
                        <option value="published" {{ old('status', $faq->status) === 'published' ? 'selected' : '' }}>Published</option>
                        <option value="draft" {{ old('status', $faq->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined" style="font-size:18px;">save</span> Update FAQ</button>
                <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
