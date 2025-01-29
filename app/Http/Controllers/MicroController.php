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
            // Get user IDs who purchased the course but are not in the group
            // $students = User::whereIn('id', function($query) use ($courseId) {
            //     $query->select('user_id')
            //           ->from('purchases') // Assuming your purchases table is named 'purchases'
            //           ->where('course_id', $courseId);
            // })
            // ->whereNotIn('id', function($query) use ($courseId) {
            //     $query->select('user_id')
            //           ->from('group_user') // Assuming your group users table is named 'group_user'
            //           ->where('course_id', $courseId);
            // })
            // ->get();

            $students = GroupUser::with('user')->where('course_id', $courseId)->whereNull('group_id')->get();

            if (!$students) {
                return response()->json(['message' => 'No new students'], 404);
            }
    
            // Prepare the response
            return response()->json([
                'message' => 'Enrollable students retrieved successfully.',
                'students' => $students
            ], 200);
        } catch (\Throwable $e) {
            Log::error("Enrollable students Error: " . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
