<?php
namespace App\Http\Controllers;

use App\Models\StudyMaterial;
use Illuminate\Http\Request;

class StudyMaterialController extends Controller
{
    /**
     * Display a listing of the study materials.
     */
    public function index()
    {
        $studyMaterials = StudyMaterial::all();
        return response()->json($studyMaterials);
    }

    /**
     * Store a new study material.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg',
            'pdf' => 'nullable|file|mimes:pdf',
            'audio' => 'nullable|file|mimes:mp3,wav',
        ]);

        $studyMaterial = new StudyMaterial();
        $studyMaterial->course_id = $validatedData['course_id'];

        if ($request->hasFile('photo')) {
            $studyMaterial->photo = $request->file('photo')->store('photos', 'public');
        }

        if ($request->hasFile('pdf')) {
            $studyMaterial->pdf = $request->file('pdf')->store('pdfs', 'public');
        }

        if ($request->hasFile('audio')) {
            $studyMaterial->audio = $request->file('audio')->store('audios', 'public');
        }

        $studyMaterial->save();

        return response()->json(['message' => 'Study material created successfully'], 201);
    }

    /**
     * Display a specific study material.
     */
    public function show($id)
    {
        $studyMaterial = StudyMaterial::findOrFail($id);
        return response()->json($studyMaterial);
    }

    /**
     * Update a specific study material.
     */
    public function update(Request $request, $id)
    {
        $studyMaterial = StudyMaterial::findOrFail($id);

        $validatedData = $request->validate([
            'photo' => 'nullable|file|mimes:jpeg,png,jpg',
            'pdf' => 'nullable|file|mimes:pdf',
            'audio' => 'nullable|file|mimes:mp3,wav',
        ]);

        if ($request->hasFile('photo')) {
            $studyMaterial->photo = $request->file('photo')->store('photos', 'public');
        }

        if ($request->hasFile('pdf')) {
            $studyMaterial->pdf = $request->file('pdf')->store('pdfs', 'public');
        }

        if ($request->hasFile('audio')) {
            $studyMaterial->audio = $request->file('audio')->store('audios', 'public');
        }

        $studyMaterial->save();

        return response()->json(['message' => 'Study material updated successfully']);
    }

    /**
     * Remove a specific study material.
     */
    public function destroy($id)
    {
        $studyMaterial = StudyMaterial::findOrFail($id);
        $studyMaterial->delete();

        return response()->json(['message' => 'Study material deleted successfully']);
    }
}
