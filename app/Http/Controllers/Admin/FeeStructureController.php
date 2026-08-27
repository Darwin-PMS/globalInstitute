<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeStructureController extends Controller
{
    public function index(Request $request)
    {
        $query = FeeStructure::with('course');

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $feeStructures = $query->latest()->paginate(15);
        $courses = Course::where('status', 'published')->orderBy('title')->get();

        return view('admin.fee-structures.index', compact('feeStructures', 'courses'));
    }

    public function create()
    {
        $courses = Course::where('status', 'published')->orderBy('title')->get();

        return view('admin.fee-structures.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'status' => 'required|in:active,inactive',
            'fee_items' => 'required|array|min:1',
            'fee_items.*.fee_type' => 'required|string|max:255',
            'fee_items.*.name' => 'required|string|max:255',
            'fee_items.*.amount' => 'required|numeric|min:0',
            'fee_items.*.mandatory' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $totalAmount = collect($validated['fee_items'])->sum('amount');

            $feeStructure = FeeStructure::create([
                'course_id' => $validated['course_id'],
                'name' => $validated['name'],
                'effective_from' => $validated['effective_from'] ?? null,
                'effective_to' => $validated['effective_to'] ?? null,
                'total_amount' => $totalAmount,
                'status' => $validated['status'],
            ]);

            foreach ($validated['fee_items'] as $index => $item) {
                FeeStructureItem::create([
                    'fee_structure_id' => $feeStructure->id,
                    'fee_type' => $item['fee_type'],
                    'name' => $item['name'],
                    'amount' => $item['amount'],
                    'mandatory' => $item['mandatory'] ?? false,
                    'sequence' => $index + 1,
                ]);
            }
        });

        return redirect()->route('admin.fee-structures.index')->with('success', 'Fee structure created successfully.');
    }

    public function edit(FeeStructure $feeStructure)
    {
        $feeStructure->load('items');
        $courses = Course::where('status', 'published')->orderBy('title')->get();

        return view('admin.fee-structures.edit', compact('feeStructure', 'courses'));
    }

    public function update(Request $request, FeeStructure $feeStructure)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'name' => 'required|string|max:255',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'status' => 'required|in:active,inactive',
            'fee_items' => 'required|array|min:1',
            'fee_items.*.fee_type' => 'required|string|max:255',
            'fee_items.*.name' => 'required|string|max:255',
            'fee_items.*.amount' => 'required|numeric|min:0',
            'fee_items.*.mandatory' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated, $feeStructure) {
            $totalAmount = collect($validated['fee_items'])->sum('amount');

            $feeStructure->update([
                'course_id' => $validated['course_id'],
                'name' => $validated['name'],
                'effective_from' => $validated['effective_from'] ?? null,
                'effective_to' => $validated['effective_to'] ?? null,
                'total_amount' => $totalAmount,
                'status' => $validated['status'],
            ]);

            $feeStructure->items()->delete();

            foreach ($validated['fee_items'] as $index => $item) {
                FeeStructureItem::create([
                    'fee_structure_id' => $feeStructure->id,
                    'fee_type' => $item['fee_type'],
                    'name' => $item['name'],
                    'amount' => $item['amount'],
                    'mandatory' => $item['mandatory'] ?? false,
                    'sequence' => $index + 1,
                ]);
            }
        });

        return redirect()->route('admin.fee-structures.index')->with('success', 'Fee structure updated successfully.');
    }

    public function destroy(FeeStructure $feeStructure)
    {
        DB::transaction(function () use ($feeStructure) {
            $feeStructure->items()->delete();
            $feeStructure->delete();
        });

        return redirect()->route('admin.fee-structures.index')->with('success', 'Fee structure deleted successfully.');
    }
}
