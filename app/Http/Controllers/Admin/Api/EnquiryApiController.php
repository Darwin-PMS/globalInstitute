<?php
namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\Request;

class EnquiryApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Enquiry::query();
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('subject', 'like', "%{$s}%");
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        return response()->json($query->orderBy('id', 'desc')->paginate($request->get('per_page', 20)));
    }

    public function show(Enquiry $enquiry)
    {
        return response()->json($enquiry);
    }

    public function update(Request $request, Enquiry $enquiry)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,contacted,converted,closed',
            'notes' => 'nullable|string|max:2000',
        ]);
        $enquiry->update($validated);
        return response()->json(['success' => true, 'message' => 'Enquiry updated.']);
    }

    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();
        return response()->json(['success' => true, 'message' => 'Enquiry deleted.']);
    }
}
