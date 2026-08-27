<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function index(Request $request)
    {
        $query = Testimonial::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('student_name', 'like', "%{$search}%");
        }

        $testimonials = $query->latest()->paginate(15);

        return view('admin.testimonials.index', compact('testimonials'));
    }

    public function create()
    {
        return view('admin.testimonials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_name' => 'required|string|max:255',
            'course' => 'nullable|string|max:255',
            'testimonial' => 'required|string',
            'batch' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'status' => 'required|in:published,draft',
            'featured' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('testimonials', 'public');
        }

        $validated['featured'] = $request->boolean('featured');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Testimonial::create($validated);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial created successfully.');
    }

    public function edit(Testimonial $testimonial)
    {
        return view('admin.testimonials.edit', compact('testimonial'));
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $validated = $request->validate([
            'student_name' => 'required|string|max:255',
            'course' => 'nullable|string|max:255',
            'testimonial' => 'required|string',
            'batch' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'status' => 'required|in:published,draft',
            'featured' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('testimonials', 'public');
        }

        $validated['featured'] = $request->boolean('featured');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $testimonial->update($validated);

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial updated successfully.');
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimonial deleted successfully.');
    }
}
