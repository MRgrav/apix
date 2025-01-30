<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\User;
use App\Models\Purchase;
use App\Models\GroupUser;
use App\Models\Group;
use App\Models\Video;
use App\Models\StudyMaterial;

class MicroController extends Controller
{
    // Redis : done
    // fetch only course names
    public function getCoursesName () {
        $key = 'micro_courses_name';

        if (Cache::has($key)) {
            $micro = json_decode(Cache::get($key), true); // Decode the JSON data
            return response()->json([
                'message' => 'Fetched courses names,',
                'courses' => $micro
            ], 200);
        }  

        $micro = Course::select('id','title')->with('category')->get();

        if ($micro->isEmpty()) {
            return response()->json(['message' => 'You have not enrolled any course yet'], 404);
        }

        Cache::put($key, $micro->toJson(), now()->addMinutes(15));

        return response()->json([
            'message' => 'Fetched courses names,',
            'courses' => $micro
        ], 200);
    }

    // redis : done
    // fetch only students not yet enrolled to any course group
    public function getStudents () {
        $key = 'micro_students_name';

        if (Cache::has($key)) {
            $micro = json_decode(Cache::get($key), true); // Decode the JSON data
            return response()->json([
                'message' => 'Fetched students names,',
                'courses' => $micro
            ], 200);
        }  

        $micro = GroupUser::with('course', 'user') // Eager load the related course and user data
                      ->whereNull('group_id')  // Assuming group_id is null for valid records
                      ->get(['course_id', 'user_id', 'user.name', 'user.phone', 'user.email', 'course.title']); // Select only needed columns

        if (!$micro) {
            return response()->json(['message' => 'You have not enrolled any course yet'], 404);
        }

        Cache::put($key, $micro->toJson(), now()->addMinutes(10));

        return response()->json([
            'message' => 'Fetched students names,',
            'courses' => $micro
        ], 200);
    }

    // redis : done
    // fetch instructors for other groups or assign new instructor
    public function getInstructors () {
        $key = 'micro_instructors_name';

        if (Cache::has($key)) {
            $micro = json_decode(Cache::get($key), true); // Decode the JSON data
            return response()->json([
                'message' => 'Fetched instructors names,',
                'users' => $micro
            ], 200);
        }

        $micro = Instructor::with(['user','course'])->get();

        if ($micro->isEmpty()) {
            $micro = User::where('role_id',1)->whereNotNull('phone_verified_at')->get();
        }

        if ($micro->isEmpty()) {
            return response()->json(['message' => 'You have no users'], 404);
        }

        Cache::put($key, $micro->toJson(), now()->addMinutes(30));

        return response()->json([
            'message' => 'Fetched instructors names,',
            'users' => $micro
        ], 200);
    }

    // redis : done
    // fetch basic users
    public function getUsers () {
        $key = 'micro_users';

        if (Cache::has($key)) {
            $micro = json_decode(Cache::get($key), true); // Decode the JSON data
            return response()->json([
                'message' => 'Fetched users,',
                'users' => $micro
            ], 200);
        }

        $micro = User::where('role_id',1)->whereNotNull('phone_verified_at')->get();

        if ($micro->isEmpty()) {
            return response()->json(['message' => 'You have no users'], 404);
        }

        Cache::put($key, $micro->toJson(), now()->addMinutes(30));

        return response()->json([
            'message' => 'Fetched users,',
            'users' => $micro
        ], 200);
    }

    // redis : done
    // fetch basic dashboard
    public function getDashboard () {
        $keyCourses = 'dash_courses';
        $keyUsers = 'dash_users';
        $keyGroups = 'dash_groups';
        $keyAcademics = 'dash_academics';
        $keyMusics = 'dash_musics';

    }

    public function enrollableStudents($courseId) {
        try {
            // Get students who are not in a group and belong to the given course
            $students = GroupUser::with('user')
                ->where('course_id', $courseId)
                ->whereNull('group_id')
                ->get();
            
            // Check if the collection is empty
            if ($students->isEmpty()) {
                return response()->json([
                    'message' => 'No enrollable students found.'
                ], 200); // No students, but it's a valid request.
            }
    
            // Return the response with students
            return response()->json([
                'message' => 'Enrollable students retrieved successfully.',
                'students' => $students
            ], 200);
            
        } catch (\Throwable $e) {
            Log::error("Enrollable students Error: " . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }


    public function getGroupsByCourseId ($courseId) {
        try {
            //code...
            $groups = Group::select('name')->where('course_id', $courseId)->get();

            if ($groups->isEmpty()) {
                return response()->json(['message' => 'No groups found'], 404);
            }

            return response()->json([
                'message'=>'Groups of this instructor',
                'groups'=>$groups
            ], 200);
        } catch (\Throwable $e) {
            //throw $e;
            Log::error("Groups by Course, ERROR: ". $e->getMessage());
            return response()->json(['message'=>'internal server error'], 500);
        }
    }
    
    public function getRecordedClasses(){
        try {
            $key = 'my_videos_' . auth()->id();

            // Check if the data is cached
            if (Cache::has($key)) {
                $videos = json_decode(Cache::get($key), true);
                return response()->json($videos, 200);
            }

            // Fetch the group IDs for the authenticated user
            $groupIds = GroupUser::where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->pluck('group_id');

            // Fetch the videos for the user's groups
            $videos = Video::with('course','group')->whereIn('group_id', $groupIds)
                ->get()
                ->toArray();

            // Cache the videos for 1 minute
            Cache::put($key, json_encode($videos), now()->addMinutes(1));

            return response()->json($videos, 200);
        } catch (\Throwable $e) {
            Log::error("Recorded classes ERROR: " . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    public function getStudyMaterials(){
        try {
            $key = 'my_materials_' . auth()->id();

            // Check if the data is cached
            if (Cache::has($key)) {
                $materials = json_decode(Cache::get($key), true);
                return response()->json($materials, 200);
            }

            // Fetch the group IDs for the authenticated user
            $groupIds = GroupUser::where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->pluck('group_id');

            // Fetch the videos for the user's groups
            $materials = StudyMaterial::with('course','group')->whereIn('group_id', $groupIds)
                ->get()
                ->toArray();

            // Cache the videos for 1 minute
            Cache::put($key, json_encode($materials), now()->addMinutes(1));

            return response()->json($materials, 200);
        } catch (\Throwable $e) {
            Log::error("Study material ERROR: " . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    public function getPaymentHistory () {
        try {
            //code...
            $payments = PaymentOrder::where('user_id',auth()->id())->get();
            if($payments->isEmpty()) {
                return response()->json(['message' => 'No purchase yet'], 404);
            }
            return response()->json($payments, 200);
        } catch (\Throwable $e) {
            //throw $e;
            Log::error("Payment History Error: ".$e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }

}
