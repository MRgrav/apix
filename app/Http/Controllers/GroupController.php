<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\TeacherClass;
use App\Models\Routine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;


class GroupController extends Controller
{
    // Method to create a new group within a course
    public function addGroup(Request $request, $courseId)
    {
        try {
            //code...
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:255',
                'instructor_id' => 'required',
            ]);
    
            $course = Course::findOrFail($courseId);
    
            // $group = $course->groups()->
            $group = Group::create([
                'name' => $request->name,
                'description' => $request->description,
                'course_id' => $courseId,   // This is not required
                'instructor_id' => $request->instructor_id,
                'created_by' => auth()->id(),
            ]);

            Routine::create([
                'group_id' => $group->id,
                'instructor_id' => $request->instructor_id,
                'session' => null
            ]);

            Cache::forget('allgroup');
    
            return response()->json(['message' => 'Group created successfully', 'group' => $group], 201);
        } catch (\Throwable $e) {
            //throw $th;
            Log::error('Error creating new group under : '.$courseId. '\n' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    // Method to assign a user to an existing group
    public function assignUserToGroup(Request $request, $groupId)
    {
        // Validate the request
        $request->validate([
            'user_id' => 'required|exists:users,id', // Validate that the user_id exists in the users table
        ]);

        // Find the group or fail with 404 if not found
        $group = Group::findOrFail($groupId);

         // Find the existing GroupUser entry for the user and their associated course
        $groupUser = GroupUser::where('user_id', $request->user_id)
                                ->where('course_id', $group->course_id) // Match by course_id
                                ->first();

         // Check if the GroupUser record exists
        if (!$groupUser) {
            return response()->json(['message' => 'User is not enrolled in the course'], 400);
        }

        // Update the group_id field in the existing GroupUser record
        $groupUser->update([
            'group_id' => $groupId, // Update only the group_id
            // 'plan_id' => $request->plan_id ?? $groupUser->plan_id, // Optionally update the plan_id if provided
        ]);

        return response()->json([
            'message' => 'User assigned to group successfully',
            'group_user' => $groupUser // Optionally return the updated GroupUser record
        ], 201);
    }
    
    // redis : done
    public function getAllGroups()
    {
        $key = 'allgroup';

        if (Cache::has($key)) {
            $groups = json_decode(Cache::get($key), true); // Decode the JSON data
            return response()->json([
                'message' => 'Groups retrieved successfully',
                'groups' => $groups
            ], 200);
        }  

        $groups = Group::with('users', 'course')->get();
        Cache::put($key, $groups->toJson(), now()->addMinutes(1));

        return response()->json([
            'message' => 'Groups retrieved successfully',
            'groups' => $groups
        ], 200);
    }

    // Method to get a group by ID
    public function getGroup($groupId)
    {
        try {
            //code...

            $key = 'group_details_' . $groupId; // Use $groupId instead of $id

            // Check if the group details are cached
            if (Cache::has($key)) {
                $groupData = json_decode(Cache::get($key), true); // Decode the JSON data
                return response()->json([
                    'message' => 'Group retrieved successfully from cache,',
                    'group' => $groupData['group'],
                    'class_status' => $groupData['class_status'],
                    'class_code' => $groupData['class_code'],
                ], 200);
            }
            
            // if $isAvailable is false means, user will not get some data [videos, class code status false]

            // Fetch the group with related data
            $group = Group::with(['users', 'course', 'videos', 'instructor'])->find($groupId);

            if (!$group) {
                return response()->json(['message' => 'Group not found'], 404);
            }

            // Get the current date
            $currentDate = Carbon::now(); // Use Laravel's now() helper for the current date
            $nextDate = $currentDate->copy()->addDay();

            // Create the class code
            $codeString = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

            // Format the group_id as a four-digit string with leading zeros
            $formattedGroupId = str_pad($groupId, 4, '0', STR_PAD_LEFT);

            // Generate the class code using the group ID and formatted date
            $new_class_code = $formattedGroupId . '&' .
                $codeString[$currentDate->year % 50] . // Year (mod 50 for index)
                $codeString[$currentDate->month - 1] . // Month (0-indexed)
                $codeString[$currentDate->day - 1]; // Day (0-indexed)

            $new_next_class_code = $formattedGroupId . '&' .
                $codeString[$nextDate->year % 50] . // Year (mod 50 for index)
                $codeString[$nextDate->month - 1] . // Month (0-indexed)
                $codeString[$nextDate->day - 1]; // Day (0-indexed)

            // Check if the class code exists for the group
            $teacherClass = TeacherClass::where('group_id', $groupId)
                                        ->orderBy('created_at', 'desc')
                                        ->first();
                // ->value('class_code');

            
            if (!$teacherClass) {
                return response()->json([
                    'message' => 'Group retrieved successfully',
                    'group' => $group,
                    'class_status' => false,
                    'class_code' => null
                ], 200);
            }

            $class_status = false;
            $code = null;
            Log::info('testing class code: ' . $teacherClass['class_code'] .'\n' . $new_class_code . '\n' . $new_next_class_code);
            if ($teacherClass['class_code'] === $new_class_code || $teacherClass['class_code'] === $new_next_class_code) {
                $code = $teacherClass['class_code'];
                $class_status = true;
            }

            // Cache the group data
            Cache::put($key, json_encode([
                'group' => $group,
                'class_status' => $class_status,
                'class_code' => $code
            ]), now()->addMinutes(1));

            return response()->json([
                'message' => 'Group retrieved successfully',
                'group' => $group,
                'class_status' => $class_status,
                'class_code' => $code
            ], 200);
        } catch (\Throwable $e) {
            //throw $e;
            Log::error("Get Group ERROR : " . $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }

    public function getGroupStudent($groupId)
    {
        try {
            //for live class link available or not
            $isAvailable = GroupUser::where('user_id', auth()->id())
                                    ->where('group_id', $groupId)
                                    ->whereColumn('class_counted', '<', 'total_classes')
                                    ->exists();

            // if class is expired or not.
            $isContentAvailable = GroupUser::where('user_id', auth()->id())
                                    ->where('group_id', $groupId)
                                    ->get();
                                    // ->where('expiry_date', '<', Carbon::now())
                                    // ->exists();
            $isRenewable = false;
            // course expiry date reached
            if (!$isContentAvailable) {
                return response()->json(['message' => 'Not Purchased yet'], 403);
            } else if ($isContentAvailable->expiry_date >= Carbon::now()) {
                Log::debug("is course expired : ". $isContentAvailable->expiry_date . ' < '. Carbon::now() . ' = '. $isContentAvailable->expiry_date < Carbon::now());
                $isRenewable = true;
            }

            // Log::debug("here: ". GroupUser::where('user_id', auth()->id())->first() . " Now: ". Carbon::now());
            $key = 'group_details_' . $groupId; // Use $groupId instead of $id

            // Check if the group details are cached
            if (Cache::has($key)) {
                $groupData = json_decode(Cache::get($key), true); // Decode the JSON data
                return response()->json([
                    'message' => 'Group retrieved successfully from cache,',
                    'group' => $groupData['group'],
                    'class_status' => $isAvailable?$groupData['class_status']:false,
                    'class_code' => $groupData['class_code'],
                    'is_renewable' => $isRenewable,
                ], 200);
            }
            
            // if $isAvailable is false means, user will not get some data [videos, class code status false]

            // Fetch the group with related data
            if (!$isContentAvailable) {
                $group = Group::with(['users', 'course', 'videos', 'instructor'])->find($groupId);
            } else {
                $group = Group::with(['users', 'course', 'instructor'])->find($groupId);
            }

            if (!$group) {
                return response()->json(['message' => 'Group not found'], 404);
            }

            // Get the current date
            $currentDate = Carbon::now(); // Use Laravel's now() helper for the current date
            $nextDate = $currentDate->copy()->addDay();

            // Create the class code
            $codeString = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

            // Format the group_id as a four-digit string with leading zeros
            $formattedGroupId = str_pad($groupId, 4, '0', STR_PAD_LEFT);

            // Generate the class code using the group ID and formatted date
            $new_class_code = $formattedGroupId . '&' .
                $codeString[$currentDate->year % 50] . // Year (mod 50 for index)
                $codeString[$currentDate->month - 1] . // Month (0-indexed)
                $codeString[$currentDate->day - 1]; // Day (0-indexed)

            $new_next_class_code = $formattedGroupId . '&' .
                $codeString[$nextDate->year % 50] . // Year (mod 50 for index)
                $codeString[$nextDate->month - 1] . // Month (0-indexed)
                $codeString[$nextDate->day - 1]; // Day (0-indexed)

            // Check if the class code exists for the group
            $teacherClass = TeacherClass::where('group_id', $groupId)
                                        ->orderBy('created_at', 'desc')
                                        ->first();

            
            if (!$teacherClass) {
                return response()->json([
                    'message' => 'Group retrieved successfully',
                    'group' => $group,
                    'class_status' => false,
                    'class_code' => null,
                    'is_renewable' => $isRenewable,
                ], 200);
            }

            $class_status = false;
            $code = null;
            Log::info('testing class code: ' . $teacherClass['class_code'] .'\n' . $new_class_code . '\n' . $new_next_class_code);
            if ($teacherClass['class_code'] === $new_class_code || $teacherClass['class_code'] === $new_next_class_code) {
                $code = $teacherClass['class_code'];
                $class_status = true;
            }

            // Cache the group data
            Cache::put($key, json_encode([
                'group' => $group,
                'class_status' => ($class_status && $isAvailable),
                'class_code' => $code,
                'is_renewable' => $isRenewable,
            ]), now()->addMinutes(1));

            return response()->json([
                'message' => 'Group retrieved successfully',
                'group' => $group,
                'class_status' => ($class_status && $isAvailable),
                'class_code' => $code,
                'is_renewable' => $isRenewable,
            ], 200);
        } catch (\Throwable $e) {
            //throw $e;
            Log::error("Get Group ERROR : " . $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }


    // Method to delete a group by ID
    public function deleteGroup($groupId)
    {
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        $group->delete();

        return response()->json(['message' => 'Group deleted successfully'], 200);
    }

    // Method to create/update live class link
    public function updateLiveClass(Request $request, $groupId) {
        try {
            //code...
            // Validate the incoming request data
            $validatedData = $request->validate([
                'live_class_link' => 'required|url', 
            ]);

            $group = Group::find($groupId);

            // Check if the group exists
            if (!$group) {
                return response()->json(['message' => 'Group not found'], 404);
            }

            // Update the live_class_link attribute
            $group->live_class_link = $validatedData['live_class_link'];

            // Save the changes to the database
            $group->save();

            // Return a success response
            return response()->json([
                'message' => 'Live class link updated successfully',
                'group' => $group // Optionally return the updated group data
            ], 200);
        } catch (\Throwable $e) {
            //throw $th;
            Log::error('Live class link update failed: ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    public function getLiveClass ($groupId) {
        try {
            //code...
            $group = Group::find($groupId);

            // Check if the group exists
            if (!$group) {
                return response()->json(['message' => 'Group not found'], 404);
            }
            return response()->json(['live_class_link' => $group->live_class_link], 200);
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Get live class link failed: ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    // private function findGroup($groupId) {
    //     // Find the group by ID
    //     $group = Group::find($groupId);

    //     // Check if the group exists
    //     if (!$group) {
    //         return response()->json(['message' => 'Group not found'], 404);
    //     }

    //     return $group;
    // }


    // redis : done
    public function myGroups(Request $request) {
        try {
            //code...
            $user = auth()->user();
            $userId = auth()->id();

            Log::info("Auth : ".auth()->id());

            if (!$userId) {
                return response()->json(['message' => 'You are not authorized'], 401);
            }

            $key = 'mygroups' . $userId;

            if (Cache::has($key)) {
                $myCourses = json_decode(Cache::get($key), true); // Decode the JSON data
                return response()->json([
                    'message' => 'Fetched enrolled courses,',
                    'courses' => $myCourses
                ], 200);
            }  

            $myCourses = GroupUser::with(['course','group','user','plan'])
                                    ->where('user_id', $userId)
                                    ->get();

            if (!$myCourses) {
                return response()->json(['message' => 'You have not enrolled any course yet'], 404);
            }

            Cache::put($key, $myCourses->toJson(), now()->addMinutes(1));

            return response()->json([
                'message' => 'Fetched enrolled courses',
                'courses' => $myCourses
            ], 200);

        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Purchase courses/groups error : " . $e->getMessage());
        }
    }

    // redis : done
    public function getInstructorsGroups()
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'User is not authenticated.'], 401);
        }   

        $key = 'instructors_groups_' . auth()->id();

        if (Cache::has($key)) {
            $groups = json_decode(Cache::get($key), true); // Decode the JSON data
            return response()->json([
                'message' => 'Groups retrieved successfully',
                'groups' => $groups
            ], 200);
        }  

        $groups = Group::with(['users', 'course'])->where('instructor_id', auth()->id())->get();

        if (!$groups) {
            return response()->json(['message'=>'no group found'], 404);
        }

        Cache::put($key, $groups->toJson(), now()->addMinutes(1));
        return response()->json([
            'message' => 'Instructors Groups retrieved successfully',
            'groups' => $groups
        ], 200);
    }

}
