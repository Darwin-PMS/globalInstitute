<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\Course;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    public function index(Request $request)
    {
        $query = Enquiry::with('course');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $enquiries = $query->latest()->paginate(15);

        return view('admin.enquiries.index', compact('enquiries'));
    }

    public function create()
    {
        $courses = Course::where('status', 'published')->orderBy('title')->get();
        return view('admin.enquiries.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|string|max:20',
            'email'   => 'nullable|email|max:255',
            'subject' => 'nullable|string|max:255',
            'source'  => 'nullable|string|max:100',
            'message' => 'nullable|string|max:1000',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        $validated['status'] = 'new';
        Enquiry::create($validated);

        return redirect()->route('admin.enquiries.index')->with('success', 'Enquiry created successfully.');
    }

    public function show(Enquiry $enquiry)
    {
        $enquiry->load('course');
        return view('admin.enquiries.show', compact('enquiry'));
    }

    public function edit(Enquiry $enquiry)
    {
        $courses = Course::where('status', 'published')->orderBy('title')->get();
        return view('admin.enquiries.edit', compact('enquiry', 'courses'));
    }

    public function update(Request $request, Enquiry $enquiry)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|string|max:20',
            'email'   => 'nullable|email|max:255',
            'subject' => 'nullable|string|max:255',
            'source'  => 'nullable|string|max:100',
            'status'  => 'required|in:new,contacted,converted',
            'message' => 'nullable|string|max:1000',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        $enquiry->update($validated);

        return redirect()->route('admin.enquiries.index')->with('success', 'Enquiry updated successfully.');
    }

    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();
        return redirect()->route('admin.enquiries.index')->with('success', 'Enquiry deleted successfully.');
    }
}
