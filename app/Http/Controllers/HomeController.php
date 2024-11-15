<?php

namespace App\Http\Controllers;

use App\Models\CourseCategory;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index()
    {
        // Retrieve all categories with their courses, including the thumbnail from uploads table and the class name
        $categories = CourseCategory::with(['courses' => function ($query) {
            $query->with(['thumbnailUpload', 'class']); // Eager load the thumbnail data and class data
        }])->get();

        // Map through categories and courses to add the thumbnail URL and class name
        $categories->map(function ($category) {
            $category->courses->map(function ($course) {
                // Set the thumbnail URL if available
                if ($course->thumbnailUpload) {
                    $course->thumbnail_url = Storage::url($course->thumbnailUpload->file_path);
                } else {
                    $course->thumbnail_url = null; // Fallback if no thumbnail is present
                }

                // Set the class name if available, otherwise default to "Music"
                $course->class_name = $course->class ? $course->class->name : 'Music';

                return $course;
            });
            return $category;
        });

        // Return the categories with their related courses, class names, and image URLs
        return response()->json($categories);
    }

    public function getPurchasedCoursesWithGroupVideos(\Request $request)
{
    $user = auth()->user();

    $courses = $user->purchasedCourses()->with(['groups' => function ($query) use ($user) {
        $query->whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with('videos');
    }])->get();

    return response()->json(['courses' => $courses]);
}

}
