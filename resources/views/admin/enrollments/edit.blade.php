@extends('layouts.admin')

@section('title', 'Edit Enrollment')

@section('content')
<form method="POST" action="{{ route('admin.enrollments.update', $enrollment->id) }}">
    @csrf
    @method('PUT')

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">badge</span>
            <h3>Enrollment Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="batch_id">Batch *</label>
                    <select name="batch_id" id="batch_id" class="form-input" required>
                        <option value="">Select Batch</option>
                        @foreach($batches ?? [] as $batch)
                            <option value="{{ $batch->id }}" {{ old('batch_id', $enrollment->batch_id) == $batch->id ? 'selected' : '' }}>{{ $batch->batch_name }}</option>
                        @endforeach
                    </select>
                    @error('batch_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status *</label>
                    <select name="status" id="status" class="form-input" required>
                        <option value="active" {{ old('status', $enrollment->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ old('status', $enrollment->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="dropped" {{ old('status', $enrollment->status) === 'dropped' ? 'selected' : '' }}>Dropped</option>
                    </select>
                    @error('status') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="fee_structure_id">Fee Structure</label>
                    <select name="fee_structure_id" id="fee_structure_id" class="form-input">
                        <option value="">Select Fee Structure</option>
                        @foreach($feeStructures ?? [] as $structure)
                            <option value="{{ $structure->id }}" {{ old('fee_structure_id', $enrollment->fee_structure_id) == $structure->id ? 'selected' : '' }}>{{ $structure->name }} - ₹{{ number_format($structure->total_amount ?? 0) }}</option>
                        @endforeach
                    </select>
                    @error('fee_structure_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="discount">Discount (₹)</label>
                    <input type="number" name="discount" id="discount" class="form-input" value="{{ old('discount', $enrollment->discount) }}" min="0" step="0.01">
                    @error('discount') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Update Enrollment
        </button>
        <a href="{{ route('admin.enrollments.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>
@endsection
