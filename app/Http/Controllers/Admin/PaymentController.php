<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\FeeInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['student.user', 'enrollment', 'feeInstallment', 'receipt']);

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('payment_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_id', 'like', "%{$search}%")
                  ->orWhere('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($q2) use ($search) {
                      $q2->where('student_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($q3) use ($search) {
                            $q3->where('name', 'like', "%{$search}%");
                        });
                  });
            });
        }

        $payments = $query->latest()->paginate(15);

        return view('admin.payments.index', compact('payments'));
    }

    public function create()
    {
        $students = Student::with('user')->where('status', 'active')->orderBy('id')->get();
        $enrollments = Enrollment::where('status', 'active')->orderBy('id')->get();
        $installments = FeeInstallment::where('status', '!=', 'paid')->orderBy('due_date')->get();

        return view('admin.payments.create', compact('students', 'enrollments', 'installments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'enrollment_id' => 'required|exists:enrollments,id',
            'fee_installment_id' => 'nullable|exists:fee_installments,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,upi,card,cheque,online',
            'transaction_id' => 'nullable|string|max:255',
            'gateway' => 'nullable|string|max:255',
            'reference_number' => 'nullable|string|max:255',
            'upi_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $lastPayment = Payment::orderBy('id', 'desc')->first();
            $nextNumber = $lastPayment ? intval(substr($lastPayment->payment_id, -6)) + 1 : 1;
            $paymentId = 'PAY-' . date('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            $enrollment = Enrollment::findOrFail($validated['enrollment_id']);
            $previousBalance = $enrollment->balance;

            if ($validated['amount'] > $previousBalance) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'Payment amount cannot exceed the outstanding balance of ' . number_format($previousBalance, 2),
                ]);
            }

            $payment = Payment::create([
                'payment_id' => $paymentId,
                'student_id' => $validated['student_id'],
                'enrollment_id' => $validated['enrollment_id'],
                'fee_installment_id' => $validated['fee_installment_id'] ?? null,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'transaction_id' => $validated['transaction_id'] ?? null,
                'gateway' => $validated['gateway'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'upi_id' => $validated['upi_id'] ?? null,
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
                'received_by' => auth()->id(),
            ]);

            if (!empty($validated['fee_installment_id'])) {
                $installment = FeeInstallment::findOrFail($validated['fee_installment_id']);
                $installmentPaidAmount = $installment->paid_amount + $validated['amount'];
                $installmentBalance = $installment->amount - $installmentPaidAmount;

                $installment->update([
                    'paid_amount' => $installmentPaidAmount,
                    'balance' => max(0, $installmentBalance),
                    'status' => $installmentBalance <= 0 ? 'paid' : 'partial',
                ]);
            }

            $newTotalPaid = $enrollment->total_paid + $validated['amount'];
            $newBalance = $enrollment->final_fee - $newTotalPaid;

            $enrollment->update([
                'total_paid' => $newTotalPaid,
                'balance' => max(0, $newBalance),
                'fee_status' => $newBalance <= 0 ? 'paid' : ($newTotalPaid > 0 ? 'partial' : 'unpaid'),
            ]);

            $lastReceipt = Receipt::orderBy('id', 'desc')->first();
            $receiptNumber = $lastReceipt
                ? 'REC-' . date('Y') . '-' . str_pad(intval(substr($lastReceipt->receipt_number, -6)) + 1, 6, '0', STR_PAD_LEFT)
                : 'REC-' . date('Y') . '-000001';

            Receipt::create([
                'receipt_number' => $receiptNumber,
                'payment_id' => $payment->id,
                'student_id' => $validated['student_id'],
                'enrollment_id' => $validated['enrollment_id'],
                'amount_paid' => $validated['amount'],
                'previous_balance' => $previousBalance,
                'remaining_balance' => max(0, $newBalance),
                'payment_method' => $validated['payment_method'],
                'transaction_id' => $validated['transaction_id'] ?? null,
                'payment_date' => $validated['payment_date'],
                'received_by' => auth()->id(),
            ]);
        });

        return redirect()->route('admin.payments.index')->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment)
    {
        $payment->load(['student.user', 'enrollment', 'feeInstallment', 'receipt', 'receivedBy']);

        return view('admin.payments.show', compact('payment'));
    }
}
