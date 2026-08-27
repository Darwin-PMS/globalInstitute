@extends('layouts.admin')

@section('title', 'Add Fee Structure')

@section('content')
<form method="POST" action="{{ route('admin.fee-structures.store') }}">
    @csrf

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">payments</span>
            <h3>Fee Structure Information</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="course_id">Course *</label>
                    <select name="course_id" id="course_id" class="form-input" required>
                        <option value="">Select Course</option>
                        @foreach($courses ?? [] as $course)
                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                        @endforeach
                    </select>
                    @error('course_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="name">Structure Name *</label>
                    <input type="text" name="name" id="name" class="form-input" value="{{ old('name') }}" required placeholder="e.g. 2024 Fee Structure">
                    @error('name') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="effective_from">Effective From *</label>
                    <input type="date" name="effective_from" id="effective_from" class="form-input" value="{{ old('effective_from') }}" required>
                    @error('effective_from') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="effective_to">Effective To</label>
                    <input type="date" name="effective_to" id="effective_to" class="form-input" value="{{ old('effective_to') }}">
                    @error('effective_to') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-input">
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">list</span>
            <h3>Fee Items</h3>
        </div>
        <div class="card-body">
            <div id="fee-items-container">
                <div class="fee-item-row" style="display:grid;grid-template-columns:1fr 1.5fr 1fr auto auto;gap:12px;align-items:end;margin-bottom:12px;padding:16px;border:1px solid var(--gray-200);border-radius:8px;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Fee Type</label>
                        <select name="items[0][fee_type]" class="form-input fee-type" required>
                            <option value="registration">Registration</option>
                            <option value="tuition">Tuition</option>
                            <option value="material">Material</option>
                            <option value="examination">Examination</option>
                            <option value="certificate">Certificate</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Name</label>
                        <input type="text" name="items[0][name]" class="form-input" placeholder="e.g. Tuition Fee - First Installment" required>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Amount (₹)</label>
                        <input type="number" name="items[0][amount]" class="form-input fee-amount" placeholder="0" min="0" step="0.01" required oninput="calculateTotal()">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Mandatory</label>
                        <label style="display:flex;align-items:center;gap:8px;padding-top:6px;">
                            <input type="checkbox" name="items[0][mandatory]" value="1" checked style="width:18px;height:18px;accent-color:var(--primary);">
                            <span style="font-size:13px;color:var(--gray-600);">Yes</span>
                        </label>
                    </div>
                    <button type="button" class="icon-btn icon-btn-delete" onclick="removeFeeItem(this)" title="Remove" style="margin-bottom:2px;">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </div>
            <button type="button" class="btn btn-outline btn-sm" onclick="addFeeItem()" style="margin-top:8px;">
                <span class="material-symbols-outlined" style="font-size:18px;">add</span> Add Fee Item
            </button>
            <div style="display:flex;justify-content:flex-end;margin-top:20px;padding-top:16px;border-top:1px solid var(--gray-200);">
                <div style="font-size:15px;color:var(--gray-700);">Total: <span style="font-family:'JetBrains Mono',monospace;font-weight:700;font-size:18px;color:var(--primary);" id="total-amount">₹0</span></div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Create Fee Structure
        </button>
        <a href="{{ route('admin.fee-structures.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
let feeItemIndex = 1;
function addFeeItem() {
    const container = document.getElementById('fee-items-container');
    const html = `
        <div class="fee-item-row" style="display:grid;grid-template-columns:1fr 1.5fr 1fr auto auto;gap:12px;align-items:end;margin-bottom:12px;padding:16px;border:1px solid var(--gray-200);border-radius:8px;">
            <div class="form-group" style="margin:0;">
                <select name="items[${feeItemIndex}][fee_type]" class="form-input fee-type" required>
                    <option value="registration">Registration</option>
                    <option value="tuition">Tuition</option>
                    <option value="material">Material</option>
                    <option value="examination">Examination</option>
                    <option value="certificate">Certificate</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <input type="text" name="items[${feeItemIndex}][name]" class="form-input" placeholder="Fee name" required>
            </div>
            <div class="form-group" style="margin:0;">
                <input type="number" name="items[${feeItemIndex}][amount]" class="form-input fee-amount" placeholder="0" min="0" step="0.01" required oninput="calculateTotal()">
            </div>
            <div class="form-group" style="margin:0;">
                <label style="display:flex;align-items:center;gap:8px;padding-top:6px;">
                    <input type="checkbox" name="items[${feeItemIndex}][mandatory]" value="1" checked style="width:18px;height:18px;accent-color:var(--primary);">
                    <span style="font-size:13px;color:var(--gray-600);">Yes</span>
                </label>
            </div>
            <button type="button" class="icon-btn icon-btn-delete" onclick="removeFeeItem(this)" title="Remove" style="margin-bottom:2px;">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
    feeItemIndex++;
}
function removeFeeItem(btn) {
    const rows = document.querySelectorAll('.fee-item-row');
    if (rows.length > 1) {
        btn.closest('.fee-item-row').remove();
        calculateTotal();
    }
}
function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.fee-amount').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('total-amount').textContent = '₹' + total.toLocaleString('en-IN');
}
</script>
@endsection
