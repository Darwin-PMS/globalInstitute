@extends('layouts.admin')

@section('title', 'New Admission')

@section('content')
<form method="POST" action="{{ route('admin.admissions.store') }}">
    @csrf

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">school</span>
            <h3>Admission Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="enquiry_id">Linked Enquiry (Optional)</label>
                    <select name="enquiry_id" id="enquiry_id" class="form-input">
                        <option value="">Select Enquiry</option>
                        @foreach($enquiries ?? [] as $enquiry)
                            <option value="{{ $enquiry->id }}" {{ old('enquiry_id') == $enquiry->id ? 'selected' : '' }}>{{ $enquiry->name }} - {{ $enquiry->subject ?? 'N/A' }}</option>
                        @endforeach
                    </select>
                    @error('enquiry_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="student_id">Student *</label>
                    <select name="student_id" id="student_id" class="form-input" required>
                        <option value="">Select Student</option>
                        @foreach($students ?? [] as $student)
                            <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>{{ $student->name }} ({{ $student->student_id ?? '' }})</option>
                        @endforeach
                    </select>
                    @error('student_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
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
                    <label class="form-label" for="batch_id">Batch *</label>
                    <select name="batch_id" id="batch_id" class="form-input" required>
                        <option value="">Select Batch</option>
                        @foreach($batches ?? [] as $batch)
                            <option value="{{ $batch->id }}" {{ old('batch_id') == $batch->id ? 'selected' : '' }}>{{ $batch->batch_name }}</option>
                        @endforeach
                    </select>
                    @error('batch_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="admission_date">Admission Date *</label>
                <input type="date" name="admission_date" id="admission_date" class="form-input" value="{{ old('admission_date', date('Y-m-d')) }}" required>
                @error('admission_date') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">payments</span>
            <h3>Fee Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="fee">Total Fee (₹) *</label>
                    <input type="number" name="fee" id="fee" class="form-input" value="{{ old('fee') }}" min="0" step="0.01" required placeholder="0" oninput="calculateAdmissionFee()">
                    @error('fee') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="discount_type">Discount Type</label>
                    <select name="discount_type" id="discount_type" class="form-input" onchange="calculateAdmissionFee()">
                        <option value="none" {{ old('discount_type', 'none') === 'none' ? 'selected' : '' }}>No Discount</option>
                        <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                        <option value="percentage" {{ old('discount_type') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                    </select>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="discount_amount">Discount Amount</label>
                    <input type="number" name="discount_amount" id="discount_amount" class="form-input" value="{{ old('discount_amount') }}" min="0" step="0.01" placeholder="0" oninput="calculateAdmissionFee()">
                    @error('discount_amount') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="discount_reason">Discount Reason</label>
                    <input type="text" name="discount_reason" id="discount_reason" class="form-input" value="{{ old('discount_reason') }}" placeholder="e.g. Early bird discount">
                    @error('discount_reason') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div style="margin-top:16px;padding:16px;background:var(--gray-50);border-radius:8px;display:flex;justify-content:space-between;">
                <span style="font-size:14px;color:var(--gray-600);">Final Fee:</span>
                <span style="font-family:'JetBrains Mono',monospace;font-weight:700;font-size:18px;color:var(--primary);" id="final-fee">₹0</span>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Create Admission
        </button>
        <a href="{{ route('admin.admissions.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
function calculateAdmissionFee() {
    const fee = parseFloat(document.getElementById('fee').value) || 0;
    const discountType = document.getElementById('discount_type').value;
    const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;
    let finalFee = fee;
    if (discountType === 'fixed') {
        finalFee = fee - discountAmount;
    } else if (discountType === 'percentage') {
        finalFee = fee - (fee * discountAmount / 100);
    }
    document.getElementById('final-fee').textContent = '₹' + Math.max(0, finalFee).toLocaleString('en-IN');
}
</script>
@endsection
