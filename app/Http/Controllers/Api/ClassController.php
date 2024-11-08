<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use Illuminate\Http\Request;
use App\Models\ClassModel; // Assuming the model for classes is named ClassModel
use Illuminate\Support\Facades\Validator;

class ClassController extends Controller
{
    /**
     * Display a listing of the classes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $classes = Classes::all();
        return response()->json($classes);
    }

    /**
     * Store a newly created class in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // Add additional validation rules here for other fields
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $class = Classes::create($request->all());

        return response()->json([
            'message' => 'Class created successfully',
            'class' => $class
        ], 201);
    }

    /**
     * Display the specified class.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */

     public function show($id)
    {
        // Fetch the class along with its courses
        $class = Classes::with('courses')->findOrFail($id);

        return response()->json($class, 200);
    }

    /**
     * Update the specified class in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $class = Classes::find($id);

        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            // Add additional validation rules here for other fields
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $class->update($request->all());

        return response()->json([
            'message' => 'Class updated successfully',
            'class' => $class
        ]);
    }

    /**
     * Remove the specified class from the database.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $class = Classes::find($id);

        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }

        $class->delete();

        return response()->json(['message' => 'Class deleted successfully']);
    }
}
