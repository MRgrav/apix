<?php

namespace App\Http\Controllers;

use App\Models\CourseCategory;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index()
    {
        // Retrieve all categories with their courses, including the thumbnail from the uploads table
        $categories = CourseCategory::with(['courses' => function ($query) {
            $query->with(['thumbnailUpload']); // Eager load the thumbnail data from uploads table
        }])->get();

        // Map through categories and courses to add the thumbnail URL
        $categories->map(function ($category) {
            $category->courses->map(function ($course) {
                if ($course->thumbnailUpload) {
                    // Attach the URL for the thumbnail to the response
                    $course->thumbnail_url = Storage::url($course->thumbnailUpload->file_path);
                } else {
                    $course->thumbnail_url = null; // Fallback if no thumbnail is present
                }
                return $course;
            });
            return $category;
        });

        // Return the categories with their related courses and image URLs
        return response()->json($categories);
    }
}
