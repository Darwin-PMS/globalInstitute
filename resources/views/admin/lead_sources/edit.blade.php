@extends('layouts.admin')

@section('title', 'Edit Lead Source: {{ $leadSource->name }}')

@section('actions')
    <a href="{{ route('admin.lead_sources.index') }}" class="btn btn-secondary">
        <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span> Back to Lead Sources
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.lead_sources.update', $leadSource->id) }}">
            @csrf
            @method('PUT')
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" value="{{ old('name', $leadSource->name) }}" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" class="form-control" name="category" value="{{ old('category', $leadSource->category) }}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-control" name="is_active">
                        <option value="1" {{ old('is_active', $leadSource->is_active) ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active', $leadSource->is_active) ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <span class="material-symbols-outlined">save</span> Save Changes
                </button>
                <a href="{{ route('admin.lead_sources.index') }}" class="btn btn-secondary">
                    <span class="material-symbols-outlined">cancel</span> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection