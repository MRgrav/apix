<?php
namespace App\Http\Controllers;

use App\Models\StudyMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
        try {
            $validatedData = $request->validate([
                'group_id' => 'required',
                'course_id' => 'required|exists:courses,id',
                'photo' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
                'pdf' => 'nullable|file|mimes:pdf',
                'audio' => 'nullable|file|mimes:mp3,wav',
            ]);

            $studyMaterial = new StudyMaterial();
            $studyMaterial->group_id = $validatedData['group_id'];
            $studyMaterial->course_id = $validatedData['course_id'];

            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $filename = time() . '_' . $file->getClientOriginalName();
                $studyMaterial->photo = $file->storeAs('photos', $filename, 'public');
            }
            
            if ($request->hasFile('pdf')) {
                $file = $request->file('pdf');
                $filename = time() . '_' . $file->getClientOriginalName();
                $studyMaterial->pdf = $file->storeAs('pdfs', $filename, 'public');
            }
            
            if ($request->hasFile('audio')) {
                $file = $request->file('audio');
                $filename = time() . '_' . $file->getClientOriginalName();
                $studyMaterial->audio = $file->storeAs('audios', $filename, 'public');
            }            

            $studyMaterial->save();

            Log::info('Study material created successfully', [
                'group_id' => $studyMaterial->group_id,
                'course_id' => $studyMaterial->course_id,
            ]);

            return response()->json(['message' => 'Study material created successfully'], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation exceptions
            Log::error('Validation error while creating study material', [
                'errors' => $e->validator->errors(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['errors' => $e->validator->errors()], 422);
        } catch (\Exception $e) {
            // Handle other exceptions
            Log::error('An error occurred while creating study material', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);
            return response()->json(['message' => 'An error occurred while creating study material', 'error' => $e->getMessage()], 500);
        }
    }


    /**
     * Display a specific group's study materials.
     */
    public function show($id)
    {
        try {
            //code...
            $key = 'group_materials' . $id;

            if (Cache::has($key)) {
                $materials = json_decode(Cache::get($key), true); // Decode the JSON data
                return response()->json([
                    'message' => 'Fetched study materials,',
                    'courses' => $materials
                ], 200);
            }

            $studyMaterials = StudyMaterial::where('group_id', $id)->get();

            if ($studyMaterials) {
                return response()->json([
                    'message' => 'No materials available'
                ], 404);
            }
            
            return response()->json([
                'message' => 'Fetched study materials',
                'courses' => $studyMaterials
            ], 200);
        } catch (\Throwable $e) {
            //throw $th;
            Log::error('An error occurred while fetching study material', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Internal server error',
            ], 500);
        }
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
            // Delete old photo if exists
            if ($studyMaterial->photo) {
                Storage::disk('public')->delete($studyMaterial->photo);
            }
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $studyMaterial->photo = $file->storeAs('photos', $filename, 'public');
        }

        if ($request->hasFile('pdf')) {
            // Delete old photo if exists
            if ($studyMaterial->pdf) {
                Storage::disk('public')->delete($studyMaterial->pdf);
            }
            $file = $request->file('pdf');
            $filename = time() . '_' . $file->getClientOriginalName();
            $studyMaterial->pdf = $file->storeAs('pdfs', $filename, 'public');
        }

        if ($request->hasFile('audio')) {
            // Delete old photo if exists
            if ($studyMaterial->audio) {
                Storage::disk('public')->delete($studyMaterial->audio);
            }
            $file = $request->file('audio');
            $filename = time() . '_' . $file->getClientOriginalName();
            $studyMaterial->audio = $file->storeAs('audios', $filename, 'public');
        }

        // if ($request->hasFile('pdf')) {
        //     $studyMaterial->pdf = $request->file('pdf')->store('pdfs', 'public');
        // }

        // if ($request->hasFile('audio')) {
        //     $studyMaterial->audio = $request->file('audio')->store('audios', 'public');
        // }

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
