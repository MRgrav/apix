<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\User;
use App\Models\Purchase;
use App\Models\GroupUser;
use App\Models\Group;
use App\Models\Video;
use App\Models\StudyMaterial;
use App\Models\PaymentOrder;
use App\Models\Routine;

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

        Cache::put($key, $micro->toJson(), now()->addMinutes(2));

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

        Cache::put($key, $micro->toJson(), now()->addMinutes(2));

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

        Cache::put($key, $micro->toJson(), now()->addMinutes(2));

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

        Cache::put($key, $micro->toJson(), now()->addMinutes(2));

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
            $videos = Video::with('course','group')
                        ->whereIn('group_id', $groupIds)
                        ->orderBy('created_at','desc')
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
            $materials = StudyMaterial::with('course','group')
                                    ->whereIn('group_id', $groupIds)
                                    ->orderBy('created_at','desc')
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
            $payments = Purchase::with('course', 'plan')
                    ->leftJoin('group_user', function ($join) {
                        $join->on('purchases.user_id', '=', 'group_user.user_id')
                            ->on('purchases.course_id', '=', 'group_user.course_id')
                            ->on('purchases.plan_id', '=', 'group_user.plan_id');
                    })
                    ->where('purchases.user_id', auth()->id())
                    ->select('purchases.*', 'group_user.*')
                    ->orderBy('purchases.created_at', 'desc')
                    ->get();

        
            // $payments = Purchase::with('course', 'plan')
            //                         ->where('user_id',auth()->id())
            //                         ->orderBy('created_at','desc')
            //                         ->get();
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

    public function getCourseStatus($courseId) {
        try {
            $data = GroupUser::with('course')
                            ->where('course_id', $courseId)
                            ->where('user_id', auth()->id())
                            ->first();

            if ($data) {
                // Check if the course is expired
                $isExpired = $data->expiry_date <= Carbon::now();

                return response()->json([
                    'expiry' => !$isExpired, // true if not expired, false if expired
                    'title' => $data->course->title,
                    'id' => $data->group_id,
                    'message' => $isExpired ? 'Course has expired.' : 'Course is active.'
                ], 200);
            } else {
                return response()->json([
                    'expiry' => null,
                    'message' => 'Course not found for the user.'
                ], 404); // Return 404 if the course is not found
            }
        } catch (\Throwable $e) {
            //throw $e;
            Log::error("Course Status ERROR: ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }


    public function upcomingClassesTodayByInstructor() {
        try {
            //code...
            $dayName = date('D', strtotime('today')); // Get the current day name (e.g., 'Mon', 'Tue', 'Wed')
            $dayColumn = strtolower($dayName); // Convert the day name to lowercase (e.g., 'mon', 'tue', 'wed')

            // $day = get week day name like 'sun', 'mon' , ...
            $routines = Routine::with('group')->where('instructor_id', auth()->id())->whereNotNull($dayColumn)->get();

            $upcomingClasses = $routines->map(function ($routine) use ($dayColumn) {
                return [
                    'group' => $routine->group,
                    'course' => $routine->group->course,
                    'time' => $routine->{$dayColumn},
                ];
            });
    
            return response()->json(['upcoming_classes' => $upcomingClasses]);

        } catch (\Throwable $e) {
            //throw $e;
            Log::error("message ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }

    public function myRenewals() {
        try {
            //code...
            $renewalKey = 'renewal'.auth()->id();
            if (Cache::has($renewalKey)) {
                $renewals = json_decode(Cache::get($renewalKey), true); // Decode the JSON data
            }
            // Fetch renewals for groups where there are 2 or fewer classes left
            // OR the expiry date is within the next month
            $renewals = GroupUser::with('course','plan')
                        ->where('user_id', auth()->id())
                        // ->where('expiry_date', '>=', Carbon::now()->addMonth()->firstOfMonth()) // Start of next month
                        // ->where('expiry_date', '<=', Carbon::now()->addMonth()->lastOfMonth())  // End of next month
                        ->whereBetween('expiry_date',[Carbon::now(), Carbon::now()->addMonth()])
                        ->get();

            if ($renewals->isEmpty()) {
                return response()->json(['message' => 'No Pending renewals'], 404);
            }

            Log::info($renewals);
            Cache::put($renewalKey, $renewals->toJson(), now()->addMinutes(45));

            return response()->json([
                'renewals' => $renewals
            ], 200);
        } catch (\Throwable $e) {
            //throw $e;
            Log::error("Renewals : ". $e->getMessage());
            return response()->json(['message' => 'Internal server error.'], 500);
        }
    }

    public function myClassSchedules() {
        try {
            //code...
            $routines = Routine::with('group')->where('instructor_id', auth()->id())->get();

            if ($routines->isEmpty()) {
                return response()->json(['message'=>'Not found'], 404);
            }

            return response()->json([
                'message' => 'fetched routines',
                'routines' => $routines,
            ], 200);
        } catch (\Throwable $e) {
            //throw $e;
            Log::error("Class Schedule : ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }

    public function getInstructorProfile($instructorId) {
        try {
            //code...
            $user = Instructor::with('user', 'course')->where('', $instructorId)->first();

            if (!$user) {
                return response()->json(['message' => 'not found'], 404);
            }

            return response()->json(['instructor' => $user], 200);
        } catch (\Throwable $e) {
            //throw $e;
            Log::error("inst profile : ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }

}
