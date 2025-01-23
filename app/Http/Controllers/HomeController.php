<?php

namespace App\Http\Controllers;

use App\Models\CourseCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\TeacherClass;
use Carbon\Carbon;

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

            if (Cache::has($key)) {
                $content = json_decode(Cache::get($key), true); // Decode the JSON data
                return response()->json([
                    'message' => 'Data fetched successfully',
                    'content' => $content
                ], 200);
            }      

            $content = Group::with(['videos'])->find($groupId);

            // Log::debug("testing group : ". Group::with(['videos'])->find($groupId));

            if (!$content) {
                return response()->json(['message' => 'Course not yet approved by classwix'], 404);
            }

            Cache::put($key, $content->toJson(), now()->addMinutes(39));

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


    public function getHomePage () {
        try {
            // Get the authenticated user's ID
            $userId = auth()->id(); // Corrected method call

            $key = 'upcoming_class_' . $userId;

            if (Cache::has($key)) {
                $myCourses = json_decode(Cache::get($key), true); // Decode the JSON data
            } else {
                // Get today's date
                $today = Carbon::now(); //->toDateString(); // Format as 'YYYY-MM-DD' for today's date
                    
                // Get all groups the user belongs to
                $groupIds = GroupUser::where('user_id', $userId)->pluck('group_id'); // Use pluck to get just the group IDs

                // Array to hold upcoming classes
                $upcomingClasses = [];

                // Loop through each group ID and get upcoming classes for today
                foreach ($groupIds as $groupId) {
                    $upcoming = TeacherClass::where('group_id', $groupId)
                        // ->where('expiry_date', '>=', $today)  // Only upcoming classes
                        // ->where('class_counted', '<', DB::raw('total_classes'))  // Ensure class_counted < total_classes
                        ->whereDate('class_time', '>=', $today->format('Y-m-d H:i:s')) // Filter for classes scheduled today
                        ->orderBy('class_time', 'desc') // Optional: order by created_at for the latest class first
                        ->orderBy('created_at', 'desc')
                        ->get(); // Fetch all classes for the given group

                    // If there are any upcoming classes, add them to the array
                    if ($upcoming->isNotEmpty()) {
                        $upcomingClasses[$groupId] = $upcoming;
                    }
                }

                Cache::put($key, $upcomingClasses->toJson(), now()->addMinutes(3));
            }
    
            

            // my enrolled classes

            $key = 'mygroups' . $userId;

            if (Cache::has($key)) {
                $myCourses = json_decode(Cache::get($key), true); // Decode the JSON data
                
            } else {
                $myCourses = GroupUser::with(['course','group','user','plan'])
                                        ->where('user_id', $userId)
                                        ->get();

                Cache::put($key, $myCourses->toJson(), now()->addMinutes(21));
            }

            return response()->json([
                'message' => 'Fetched home,',
                'upcomings' => $upcomingClasses,
                'courses' => $myCourses
            ], 200);

            // plan expiry

        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Web Home error : ". $e->getMessage());
            return response()->json(['message'=>'Internal server error'], 500);
        }
    }

}
