<?php

namespace App\Http\Controllers;

use App\Models\CourseCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Group;

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

    public function getPurchasedCourseDetails($groupId) {
        try {
            //code...
            $userId = auth()->id();

            // If no user is authenticated, return an Unauthorized response
            if (!$userId) {
                return response()->json(['message' => 'User not authenticated'], 401); // Unauthorized
            }

            $key = $id . 'group' . $userId;

            if (Cache::has('classwix_' . $key)) {
                $content = json_decode(Cache::get('classwix_' . $key), true); // Decode the JSON data
                return response()->json([
                    'message' => 'Data fetched successfully',
                    'content' => $content
                ], 200);
            } 
            if (Cache::has($key)) {
                $content = json_decode(Cache::get($key), true); // Decode the JSON data
                return response()->json([
                    'message' => 'Data fetched successfully',
                    'content' => $content
                ], 200);
            }      

            $content = Group::with(['video'])->find($groupId);

            Log::debug("testing group : ". Group::with(['video'])->find($groupId));

            if (!$content) {
                return response()->json(['message' => 'Course not yet approved by classwix'], 404);
            }

            Cache::put($key, $content->toJson(), now()->addHour());

            return response()->json([
                'message' => 'Data fetched successfully',
                'content' => $content
            ], 200);
        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Error fetching student's course data: ". $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

}
