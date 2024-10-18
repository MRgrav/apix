<?php
namespace App\Http\Controllers;

use App\Models\Workshop;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    // Get all workshops
    public function index()
    {
        return response()->json(Workshop::all(), 200);
    }

    // Create a new workshop
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required',
            'link' => 'required|url',
        ]);

        $workshop = Workshop::create($request->all());

        return response()->json($workshop, 201);
    }

    // Show a single workshop
    public function show($id)
    {
        $workshop = Workshop::find($id);
        if (!$workshop) {
            return response()->json(['message' => 'Workshop not found'], 404);
        }

        return response()->json($workshop, 200);
    }

    // Update an existing workshop
    public function update(Request $request, $id)
    {
        $workshop = Workshop::find($id);
        if (!$workshop) {
            return response()->json(['message' => 'Workshop not found'], 404);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required',
            'link' => 'sometimes|required|url',
        ]);

        $workshop->update($request->all());

        return response()->json($workshop, 200);
    }

    // Delete a workshop
    public function destroy($id)
    {
        $workshop = Workshop::find($id);
        if (!$workshop) {
            return response()->json(['message' => 'Workshop not found'], 404);
        }

        $workshop->delete();

        return response()->json(['message' => 'Workshop deleted'], 200);
    }
}
