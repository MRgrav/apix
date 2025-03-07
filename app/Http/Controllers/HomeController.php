<?php

namespace App\Http\Controllers;

use App\Models\CourseCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\TeacherClass;
use App\Models\StudyMaterial;
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

            Cache::put($key, $content->toJson(), now()->addMinutes(1));

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


    public function getHomePage(){
        try {
            $userId = auth()->id();

            // Fetch group IDs where class_counted is less than or equal to total_classes
            $groupIds = GroupUser::where('user_id', $userId)
                                ->whereColumn('class_counted', '<=', 'total_classes')
                                ->pluck('group_id');

            $renewalKey = 'renewal'.auth()->id();
            if (Cache::has($renewalKey)) {
                $renewals = json_decode(Cache::get($renewalKey), true); // Decode the JSON data
            } else {
                // Fetch renewals for groups where there are 2 or fewer classes left
                // OR the expiry date is within the next month
                $renewals = GroupUser::with('course','plan')
                            ->where('user_id', $userId)
                            // ->where(function($query) {
                            //     $query->whereBetween('expiry_date', [
                            //         Carbon::now()->startOfMonth()->addMonth(),
                            //         Carbon::now()->endOfMonth()->addMonth()
                            //     ]);
                            // })
                            // ->where('expiry_date', '>=', Carbon::now()->addMonth()->firstOfMonth()) // Start of next month
                            // ->where('expiry_date', '<=', Carbon::now()->addMonth()->lastOfMonth())  // End of next month
                            ->whereBetween('expiry_date',[Carbon::now(), Carbon::now()->addMonth()])
                            ->get();
                Log::info($renewals);
                Cache::put($renewalKey, $renewals->toJson(), now()->addMinutes(45));
            }  


            $upcomingClasses = $this->getUpcomingClasses($groupIds, $userId);
            $myCourses = $this->getMyCourses($userId);
            $studyMaterials = $this->getStudyMaterials($groupIds, $userId);

            return response()->json([
                'message' => 'Fetched home,',
                'upcomings' => $upcomingClasses,
                'courses' => $myCourses,
                'materials' => $studyMaterials,
                'renewals' => $renewals,
            ], 200);
        } catch (\Throwable $e) {
            Log::error("Web Home error : " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    private function getUpcomingClasses($groupIds, $userId){
        $upcomingKey = 'upcoming_class_' . $userId;

        // if (Cache::has($upcomingKey)) {
        //     return collect(json_decode(Cache::get($upcomingKey), true));
        // }

        // $upcomingClasses = collect();

        // foreach ($groupIds as $groupId) {
        //         $upcoming = TeacherClass::with(['group', 'group.course'])
        //                                 ->where('group_id', $groupId)
        //                                 ->whereDate('class_time', '>=', Carbon::now()->format('Y-m-d'))
        //                                 ->orderBy('class_time', 'desc')
        //                                 ->first();

        //         if ($upcoming) {
        //             $upcomingClasses = $upcomingClasses->merge([$upcoming]);
        //         }  
        // }

        $upcomingClasses = TeacherClass::with(['group', 'group.course'])
                                        ->whereDate('class_time', '>=', Carbon::now()->format('Y-m-d'))
                                        ->groupBy('group_id')
                                        ->where('user_id', $userId)
                                        ->orderBy('class_time', 'desc')
                                        ->first();

        // Cache::put($upcomingKey, $upcomingClasses->toJson(), now()->addMinutes(1));

        return $upcomingClasses;
    }

    private function getMyCourses($userId){
        $key = 'mygroups' . $userId;

        if (Cache::has($key)) {
            return json_decode(Cache::get($key), true);
        }

        $myCourses = GroupUser::with(['course', 'group', 'user', 'plan'])
            ->where('user_id', $userId)
            ->whereNotNull('group_id')
            ->get();

        Cache::put($key, $myCourses->toJson(), now()->addMinutes(1));

        return $myCourses;
    }

    private function getStudyMaterials($groupIds, $userId){
        $key = 'material_home' . $userId;
    
        // if (Cache::has($key)) {
        //     return collect(json_decode(Cache::get($key), true));
        // }
    
        // $studyMaterials = collect();
    
        // foreach ($groupIds as $groupId) {
        //     $material = StudyMaterial::with(['course', 'group'])
        //         ->where('group_id', $groupId)
        //         ->orderBy('created_at', 'desc')
        //         ->first();

        //     Log::info("Is getting : ", $material);
    
        //     if ($material) {
        //         $studyMaterials = $studyMaterials->merge([$material]);
        //     }
        // }

        $studyMaterials = StudyMaterial::with(['course', 'group'])
                            ->groupBy('group_id')
                            ->where('user_id', $userId)
                            ->orderBy('created_at', 'desc')
                            ->get();
    
        Cache::put($key, $studyMaterials->toJson(), now()->addMinutes(1));
    
        return $studyMaterials;
    }
    
   

    
    
    

}
