@extends('layouts.admin')

@section('title', 'New Enrollment')

@section('content')
<form method="POST" action="{{ route('admin.enrollments.store') }}">
    @csrf

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">badge</span>
            <h3>Enrollment Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
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
                <div class="form-group">
                    <label class="form-label" for="course_id">Course *</label>
                    <select name="course_id" id="course_id" class="form-input" required onchange="filterBatches()">
                        <option value="">Select Course</option>
                        @foreach($courses ?? [] as $course)
                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->title }}</option>
                        @endforeach
                    </select>
                    @error('course_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="batch_id">Batch *</label>
                    <select name="batch_id" id="batch_id" class="form-input" required>
                        <option value="">Select Batch</option>
                        @foreach($batches ?? [] as $batch)
                            <option value="{{ $batch->id }}" data-course="{{ $batch->course_id }}" {{ old('batch_id') == $batch->id ? 'selected' : '' }}>{{ $batch->batch_name }}</option>
                        @endforeach
                    </select>
                    @error('batch_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="admission_id">Linked Admission</label>
                    <select name="admission_id" id="admission_id" class="form-input">
                        <option value="">None</option>
                        @foreach($admissions ?? [] as $admission)
                            <option value="{{ $admission->id }}" {{ old('admission_id') == $admission->id ? 'selected' : '' }}>{{ $admission->admission_number }} - {{ $admission->student->name ?? '' }}</option>
                        @endforeach
                    </select>
                    @error('admission_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="fee_structure_id">Fee Structure</label>
                    <select name="fee_structure_id" id="fee_structure_id" class="form-input">
                        <option value="">Select Fee Structure</option>
                        @foreach($feeStructures ?? [] as $structure)
                            <option value="{{ $structure->id }}" data-course="{{ $structure->course_id }}" {{ old('fee_structure_id') == $structure->id ? 'selected' : '' }}>{{ $structure->name }} - ₹{{ number_format($structure->total_amount ?? 0) }}</option>
                        @endforeach
                    </select>
                    @error('fee_structure_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="discount">Discount (₹)</label>
                    <input type="number" name="discount" id="discount" class="form-input" value="{{ old('discount') }}" min="0" step="0.01" placeholder="0" oninput="calculateEnrollmentFee()">
                    @error('discount') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">payments</span>
            <h3>Fee Summary</h3>
        </div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;justify-content:space-between;padding:8px 0;">
                    <span style="font-size:14px;color:var(--gray-600);">Total Fee:</span>
                    <span style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--gray-900);" id="summary-total">₹0</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-top:1px solid var(--gray-200);">
                    <span style="font-size:14px;color:var(--gray-600);">Discount:</span>
                    <span style="font-family:'JetBrains Mono',monospace;font-weight:600;color:var(--danger);" id="summary-discount">-₹0</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:12px 0;border-top:2px solid var(--gray-200);">
                    <span style="font-size:15px;font-weight:600;color:var(--gray-900);">Final Fee:</span>
                    <span style="font-family:'JetBrains Mono',monospace;font-weight:700;font-size:20px;color:var(--primary);" id="summary-final">₹0</span>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Create Enrollment
        </button>
        <a href="{{ route('admin.enrollments.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
function filterBatches() {
    const courseId = document.getElementById('course_id').value;
    const batchSelect = document.getElementById('batch_id');
    const currentBatch = batchSelect.value;
    batchSelect.innerHTML = '<option value="">Select Batch</option>';
    const batches = @json($batches ?? []);
    batches.forEach(batch => {
        if (!courseId || batch.course_id == courseId) {
            const option = document.createElement('option');
            option.value = batch.id;
            option.textContent = batch.name;
            if (batch.id == currentBatch) option.selected = true;
            batchSelect.appendChild(option);
        }
    });
    filterFeeStructures();
}
function filterFeeStructures() {
    const courseId = document.getElementById('course_id').value;
    const select = document.getElementById('fee_structure_id');
    const current = select.value;
    select.innerHTML = '<option value="">Select Fee Structure</option>';
    const structures = @json($feeStructures ?? []);
    structures.forEach(s => {
        if (!courseId || s.course_id == courseId) {
            const option = document.createElement('option');
            option.value = s.id;
            option.textContent = s.name + ' - ₹' + Number(s.total_amount || 0).toLocaleString('en-IN');
            if (s.id == current) option.selected = true;
            select.appendChild(option);
        }
    });
    calculateEnrollmentFee();
}
function calculateEnrollmentFee() {
    const structureSelect = document.getElementById('fee_structure_id');
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const structures = @json($feeStructures ?? []);
    let total = 0;
    const selected = structures.find(s => s.id == structureSelect.value);
    if (selected) total = parseFloat(selected.total_amount) || 0;
    const finalFee = Math.max(0, total - discount);
    document.getElementById('summary-total').textContent = '₹' + total.toLocaleString('en-IN');
    document.getElementById('summary-discount').textContent = '-₹' + discount.toLocaleString('en-IN');
    document.getElementById('summary-final').textContent = '₹' + finalFee.toLocaleString('en-IN');
}
</script>
@endsection
