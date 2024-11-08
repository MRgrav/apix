<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use App\Models\User;
use App\Models\Course;
use App\Models\Group;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    // Assign an existing user as an instructor to a course (and optionally a group)
    public function assignInstructor(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'group_id' => 'nullable|exists:groups,id',
        ]);

        $instructor = Instructor::create([
            'user_id' => $request->user_id,
            'course_id' => $request->course_id,
            'group_id' => $request->group_id,
        ]);

        return response()->json([
            'message' => 'Instructor assigned successfully',
            'instructor' => $instructor
        ], 201);
    }

    // Remove an instructor from a course (and optionally a group)
    public function removeInstructor($id)
    {
        $instructor = Instructor::findOrFail($id);
        $instructor->delete();

        return response()->json([
            'message' => 'Instructor removed successfully'
        ], 200);
    }

    // Retrieve instructors for a specific course
    public function getInstructorsByCourse($courseId)
    {
        $instructors = Instructor::where('course_id', $courseId)->with('user')->get();

        return response()->json([
            'instructors' => $instructors
        ]);
    }
}
