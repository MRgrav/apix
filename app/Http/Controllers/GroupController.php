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
        ]);

        $course = Course::findOrFail($courseId);

        $group = $course->groups()->create([
            'name' => $request->name,
            'description' => $request->description,
            'created_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'Group created successfully', 'group' => $group], 201);
    }

    // Method to assign a user to an existing group
    public function assignUserToGroup(Request $request, $groupId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $group = Group::findOrFail($groupId);
        $group->users()->attach($request->user_id);

        return response()->json(['message' => 'User assigned to group successfully']);
    }
    
    public function getAllGroups()
    {
        $groups = Group::with('users', 'course')->get();

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

            $group = findGroup($groupId);

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
            $group = findGroup($groupId);
            return response()->json(['live_class_link' => $group->live_class_link], 200);
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Get live class link failed: ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    private function findGroup($groupId) {
        // Find the group by ID
        $group = Group::find($groupId);

        // Check if the group exists
        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        return $group;
    }

    public function myGroups() {
        try {
            //code...
            $userId = auth()->id();

            if (!$userId) {
                return response()->json(['message' => 'You are not authorized'], 401);
            }

            $key = 'mygroups' . $userId;

            if (Cache::has('classwix_' . $key)) {
                $myCourses = json_decode(Cache::get('classwix_' . $key), true); // Decode the JSON data
                return response()->json([
                    'message' => 'Fetched enrolled courses',
                    '$courses' => $myCourses
                ], 200);
            } 
            if (Cache::has($key)) {
                $myCourses = json_decode(Cache::get($key), true); // Decode the JSON data
                return response()->json([
                    'message' => 'Fetched enrolled courses',
                    '$courses' => $myCourses
                ], 200);
            }  

            $myCourses = GroupUser::with(['course','group','user'])
                                    ->where('user_id', $userId)
                                    ->get();

            if (!$myCourses) {
                return response()->json(['message' => 'You have not enrolled any course yet'], 404);
            }

            Cache::put($key, $myCourses->toJson(), now()->addHour());

            return response()->json([
                'message' => 'Fetched enrolled courses',
                '$courses' => $myCourses
            ], 200);

        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Purchase courses/groups error : " . $e->getMessage());
        }
    }

}
