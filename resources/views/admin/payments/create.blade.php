@extends('layouts.admin')

@section('title', 'Record Payment')

@section('content')
<form method="POST" action="{{ route('admin.payments.store') }}">
    @csrf

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">person</span>
            <h3>Select Student & Enrollment</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="student_id">Student *</label>
                    <select name="student_id" id="student_id" class="form-input" required onchange="filterEnrollments()">
                        <option value="">Select Student</option>
                        @foreach($students ?? [] as $student)
                            <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>{{ $student->name }} ({{ $student->student_id ?? '' }})</option>
                        @endforeach
                    </select>
                    @error('student_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="enrollment_id">Enrollment *</label>
                    <select name="enrollment_id" id="enrollment_id" class="form-input" required onchange="filterInstallments()">
                        <option value="">Select Enrollment</option>
                        @foreach($enrollments ?? [] as $enrollment)
                            <option value="{{ $enrollment->id }}" data-student="{{ $enrollment->student_id }}" data-balance="{{ $enrollment->balance ?? 0 }}" {{ old('enrollment_id') == $enrollment->id ? 'selected' : '' }}>
                                {{ $enrollment->enrollment_number }} - {{ $enrollment->course->title ?? '' }} (Balance: ₹{{ number_format($enrollment->balance ?? 0) }})
                            </option>
                        @endforeach
                    </select>
                    @error('enrollment_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="installment_id">Installment (Optional)</label>
                <select name="installment_id" id="installment_id" class="form-input">
                    <option value="">General Payment</option>
                    @foreach($installments ?? [] as $installment)
                        <option value="{{ $installment->id }}" data-enrollment="{{ $installment->enrollment_id }}" {{ old('installment_id') == $installment->id ? 'selected' : '' }}>
                            Installment - {{ $installment->due_date ? \Carbon\Carbon::parse($installment->due_date)->format('d M Y') : '' }} (₹{{ number_format($installment->amount ?? 0) }})
                        </option>
                    @endforeach
                </select>
                @error('installment_id') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div style="margin-top:12px;padding:12px 16px;background:var(--gray-50);border-radius:8px;">
                <span style="font-size:13px;color:var(--gray-500);">Current Balance: </span>
                <span style="font-family:'JetBrains Mono',monospace;font-weight:700;font-size:16px;color:var(--primary);" id="current-balance">₹0</span>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <span class="material-symbols-outlined" style="color:var(--primary);">payments</span>
            <h3>Payment Details</h3>
        </div>
        <div class="card-body">
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="amount">Amount (₹) *</label>
                    <input type="number" name="amount" id="amount" class="form-input" value="{{ old('amount') }}" min="0" step="0.01" required placeholder="0">
                    @error('amount') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="payment_date">Payment Date *</label>
                    <input type="date" name="payment_date" id="payment_date" class="form-input" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                    @error('payment_date') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="method">Payment Method *</label>
                    <select name="method" id="method" class="form-input" required onchange="toggleTransactionFields()">
                        <option value="">Select Method</option>
                        <option value="cash" {{ old('method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="upi" {{ old('method') === 'upi' ? 'selected' : '' }}>UPI</option>
                        <option value="gpay" {{ old('method') === 'gpay' ? 'selected' : '' }}>Google Pay</option>
                        <option value="phonepe" {{ old('method') === 'phonepe' ? 'selected' : '' }}>PhonePe</option>
                        <option value="paytm" {{ old('method') === 'paytm' ? 'selected' : '' }}>Paytm</option>
                        <option value="bank_transfer" {{ old('method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="card" {{ old('method') === 'card' ? 'selected' : '' }}>Card</option>
                        <option value="net_banking" {{ old('method') === 'net_banking' ? 'selected' : '' }}>Net Banking</option>
                        <option value="gateway" {{ old('method') === 'gateway' ? 'selected' : '' }}>Payment Gateway</option>
                        <option value="other" {{ old('method') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('method') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="transaction_id">Transaction ID</label>
                    <input type="text" name="transaction_id" id="transaction_id" class="form-input" value="{{ old('transaction_id') }}" placeholder="e.g. TXN123456789">
                    @error('transaction_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group" id="upi-id-group" style="display:none;">
                    <label class="form-label" for="upi_id">UPI ID</label>
                    <input type="text" name="upi_id" id="upi_id" class="form-input" value="{{ old('upi_id') }}" placeholder="e.g. name@upi">
                    @error('upi_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="reference_number">Reference Number</label>
                    <input type="text" name="reference_number" id="reference_number" class="form-input" value="{{ old('reference_number') }}" placeholder="e.g. Cheque/DD number">
                    @error('reference_number') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="notes">Notes</label>
                <textarea name="notes" id="notes" class="form-input" rows="2" placeholder="Additional notes...">{{ old('notes') }}</textarea>
                @error('notes') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div style="display:flex;gap:12px;padding-bottom:24px;">
        <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" style="font-size:18px;">save</span> Record Payment
        </button>
        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
function filterEnrollments() {
    const studentId = document.getElementById('student_id').value;
    const select = document.getElementById('enrollment_id');
    const current = select.value;
    select.innerHTML = '<option value="">Select Enrollment</option>';
    const enrollments = @json($enrollments ?? []);
    enrollments.forEach(e => {
        if (!studentId || e.student_id == studentId) {
            const option = document.createElement('option');
            option.value = e.id;
            option.dataset.balance = e.balance || 0;
            option.textContent = e.enrollment_number + ' - ' + (e.course?.title || '') + ' (Balance: ₹' + Number(e.balance || 0).toLocaleString('en-IN') + ')';
            if (e.id == current) option.selected = true;
            select.appendChild(option);
        }
    });
    filterInstallments();
}
function filterInstallments() {
    const enrollmentId = document.getElementById('enrollment_id').value;
    const select = document.getElementById('installment_id');
    const current = select.value;
    select.innerHTML = '<option value="">General Payment</option>';
    const installments = @json($installments ?? []);
    installments.forEach(i => {
        if (!enrollmentId || i.enrollment_id == enrollmentId) {
            const option = document.createElement('option');
            option.value = i.id;
            option.textContent = 'Installment - ' + (i.due_date || '') + ' (₹' + Number(i.amount || 0).toLocaleString('en-IN') + ')';
            if (i.id == current) option.selected = true;
            select.appendChild(option);
        }
    });
    updateBalance();
}
function updateBalance() {
    const option = document.getElementById('enrollment_id').options[document.getElementById('enrollment_id').selectedIndex];
    const balance = option ? parseFloat(option.dataset.balance) || 0 : 0;
    document.getElementById('current-balance').textContent = '₹' + balance.toLocaleString('en-IN');
}
function toggleTransactionFields() {
    const method = document.getElementById('method').value;
    const upiGroup = document.getElementById('upi-id-group');
    if (['upi', 'gpay', 'phonepe', 'paytm'].includes(method)) {
        upiGroup.style.display = 'block';
    } else {
        upiGroup.style.display = 'none';
    }
}
filterEnrollments();
toggleTransactionFields();
</script>
@endsection
