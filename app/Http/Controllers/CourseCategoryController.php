<?php

namespace App\Http\Controllers;

use App\Models\CourseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseCategoryController extends Controller
{
    public function index()
    {
        // Retrieve all categories
        return CourseCategory::all();
    }

    public function show($id)
    {
        // Retrieve a specific category by ID
        return CourseCategory::findOrFail($id);
    }

    public function store(Request $request)
    {
        // Log incoming request data for debugging
        Log::info('Store method called with data:', $request->all());

        // Validate the request data
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:course_categories,slug|max:255',
            'icon' => 'nullable|exists:uploads,id',
            'thumbnail' => 'nullable|exists:uploads,id',
            'parent_id' => 'nullable|exists:course_categories,id',
            'user_id' => 'required|exists:users,id',
            'status_id' => 'required|exists:statuses,id',
            'is_popular' => 'required|boolean'
        ]);

        try {
            // Create a new category instance and assign values manually
            $category = new CourseCategory();
            $category->title = $validatedData['title'];
            $category->slug = $validatedData['slug'];
            $category->icon = $validatedData['icon'] ?? null;
            $category->thumbnail = $validatedData['thumbnail'] ?? null;
            $category->parent_id = $validatedData['parent_id'] ?? null;
            $category->user_id = $validatedData['user_id'];
            $category->status_id = $validatedData['status_id'];
            $category->is_popular = $validatedData['is_popular'];

            // Log before saving
            Log::info('Before saving category:', ['category' => $category]);

            // Save the category
            if ($category->save()) {
                Log::info('Course category saved successfully:', ['id' => $category->id]);
                return response()->json($category, 201);
            } else {
                Log::error('Failed to save course category.');
                return response()->json(['error' => 'Failed to save course category.'], 500);
            }
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Error while creating course category:', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to create course category.'], 500);
        }
    }


    public function update(Request $request, $id)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:course_categories,slug,'.$id,
            'icon' => 'nullable|exists:uploads,id',
            'thumbnail' => 'nullable|exists:uploads,id',
            'parent_id' => 'nullable|exists:course_categories,id',
        ]);

        // Find and update the category
        $category = CourseCategory::findOrFail($id);
        $category->update($validatedData);

        return response()->json($category, 200);
    }

    public function destroy($id)
    {
        // Find and delete the category
        $category = CourseCategory::findOrFail($id);
        $category->delete();

        return response()->json(null, 204);
    }
}
