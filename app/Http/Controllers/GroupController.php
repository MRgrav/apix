<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Group;
use Illuminate\Http\Request;

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
}
