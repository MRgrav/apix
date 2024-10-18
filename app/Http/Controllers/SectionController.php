<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        // Retrieve all sections
        return Section::with('course')->get();
    }

    public function show($id)
    {
        // Retrieve a specific section by ID
        return Section::with('course')->findOrFail($id);
    }

    public function store(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'order' => 'integer',
        ]);

        // Create a new section
        $section = Section::create($validatedData);
        return response()->json($section, 201);
    }

    public function update(Request $request, $id)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'order' => 'integer',
        ]);

        // Find and update the section
        $section = Section::findOrFail($id);
        $section->update($validatedData);

        return response()->json($section, 200);
    }

    public function destroy($id)
    {
        // Find and delete the section
        $section = Section::findOrFail($id);
        $section->delete();

        return response()->json(null, 204);
    }
}
