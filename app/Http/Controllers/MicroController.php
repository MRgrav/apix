<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

use App\Models\Course;
use App\Models\Instructor;

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

        $micro = Course::select('id','title')->get();

        if (!$micro) {
            return response()->json(['message' => 'You have not enrolled any course yet'], 404);
        }

        Cache::put($key, $micro->toJson(), now()->addHour());

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
                'courses' => $micro
            ], 200);
        }

        $micro = Instructor::all();

        if (!$micro) {
            $micro = User::where('role',1)->whereNotNull('phone_verified_at')->get();
        }

        if (!$micro) {
            return response()->json(['message' => 'You have no users'], 404);
        }

        Cache::put($key, $micro->toJson(), now()->addMinutes(30));

        return response()->json([
            'message' => 'Fetched instructors names,',
            'courses' => $micro
        ], 200);
    }
}
