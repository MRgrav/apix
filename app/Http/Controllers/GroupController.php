<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Group;
use App\Models\GroupUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;


class GroupController extends Controller
{
    // Method to create a new group within a course
    public function addGroup(Request $request, $courseId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'course_id' => 'required',
        ]);

        $course = Course::findOrFail($courseId);

        $group = $course->groups()->create([
            'name' => $request->name,
            'description' => $request->description,
            'course_id' => $request->course_id,
            'created_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'Group created successfully', 'group' => $group], 201);
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
        Cache::put($key, $groups->toJson(), now()->addHour());

        return response()->json([
            'message' => 'Groups retrieved successfully',
            'groups' => $groups
        ], 200);
    }

    // Method to get a group by ID
    public function getGroup($groupId)
    {
        $group = Group::with('users')->find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        return response()->json([
            'message' => 'Group retrieved successfully',
            'group' => $group
        ], 200);
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

            Cache::put($key, $myCourses->toJson(), now()->addHour());

            return response()->json([
                'message' => 'Fetched enrolled courses',
                'courses' => $myCourses
            ], 200);

        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Purchase courses/groups error : " . $e->getMessage());
        }
    }

}
