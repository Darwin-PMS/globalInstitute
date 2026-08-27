<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use Illuminate\Http\Request;

class LeadSourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $leadSources = LeadSource::all();
        return view('admin.lead_sources.index', compact('leadSources'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.lead_sources.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        LeadSource::create($validated);

        return redirect()->route('admin.lead_sources.index')
            ->with('success', 'Lead source created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeadSource $leadSource)
    {
        return view('admin.lead_sources.edit', compact('leadSource'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeadSource $leadSource)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $leadSource->update($validated);

        return redirect()->route('admin.lead_sources.index')
            ->with('success', 'Lead source updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeadSource $leadSource)
    {
        $leadSource->delete();

        return redirect()->route('admin.lead_sources.index')
            ->with('success', 'Lead source deleted successfully.');
    }
}