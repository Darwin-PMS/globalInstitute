<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $rooms = $query->latest()->paginate(15);

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create()
    {
        return view('admin.rooms.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'room_number' => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:1',
            'type' => 'required|in:classroom,computer_lab,cad_lab,cnc_lab,workshop,other',
            'status' => 'required|in:active,maintenance,inactive',
        ]);

        Room::create($validated);

        return redirect()->route('admin.rooms.index')->with('success', 'Room created successfully.');
    }

    public function edit(Room $room)
    {
        return view('admin.rooms.edit', compact('room'));
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'room_number' => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:1',
            'type' => 'required|in:classroom,computer_lab,cad_lab,cnc_lab,workshop,other',
            'status' => 'required|in:active,maintenance,inactive',
        ]);

        $room->update($validated);

        return redirect()->route('admin.rooms.index')->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room)
    {
        $room->delete();

        return redirect()->route('admin.rooms.index')->with('success', 'Room deleted successfully.');
    }
}
