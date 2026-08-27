@extends('layouts.admin')

@section('title', 'Edit Module')

@section('content')
<div class="card" style="max-width:640px;">
    <div class="card-header">
        <span class="material-symbols-outlined" style="color:var(--primary);">view_module</span>
        <h3>Edit Module</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.course-modules.update', $module->id) }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label" for="course_id">Course *</label>
                <select name="course_id" id="course_id" class="form-input" required>
                    <option value="">Select Course</option>
                    @foreach($courses ?? [] as $course)
                        <option value="{{ $course->id }}" {{ old('course_id', $module->course_id) == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                    @endforeach
                </select>
                @error('course_id') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="name">Module Name *</label>
                <input type="text" name="name" id="name" class="form-input" value="{{ old('name', $module->name) }}" required>
                @error('name') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea name="description" id="description" class="form-input" rows="3">{{ old('description', $module->description) }}</textarea>
                @error('description') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="duration">Duration</label>
                    <input type="text" name="duration" id="duration" class="form-input" value="{{ old('duration', $module->duration) }}">
                    @error('duration') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="sequence">Sequence Order</label>
                    <input type="number" name="sequence" id="sequence" class="form-input" value="{{ old('sequence', $module->sequence) }}">
                    @error('sequence') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-input">
                    <option value="active" {{ old('status', $module->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $module->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div style="display:flex;gap:12px;margin-top:24px;">
                <button type="submit" class="btn btn-primary"><span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Module</button>
                <a href="{{ route('admin.course-modules.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
