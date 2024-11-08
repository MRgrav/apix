<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Trial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrialController extends Controller
{
    // Method to start a trial for a user
    public function startTrial(Request $request, $courseId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $trial = Trial::create([
            'user_id' => $request->user_id,
            'course_id' => $courseId,
            'is_active' => true, // Assuming trials are active when created
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Trial started successfully.',
            'trial' => $trial
        ], 201);
    }

    // Method to retrieve trial users for a specific course
    public function getTrialUsers($courseId)
    {
        $course = Course::findOrFail($courseId);

        $trialUsers = DB::table('trials')
            ->where('course_id', $courseId)
            ->where('is_active', true)
            ->join('users', 'trials.user_id', '=', 'users.id')
            ->select('users.id as user_id', 'users.name', 'users.email')
            ->get();

        return response()->json([
            'course' => $course->title,
            'trial_users' => $trialUsers
        ], 200);
    }

    // Method to set the trial link and description for a specific trial user
    public function setTrialLinkAndDescription(Request $request, $courseId, $userId)
    {
        $request->validate([
            'trial_link' => 'required|url',
            'description' => 'required|string'
        ]);

        $updated = DB::table('trials')
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->update([
                'trial_link' => $request->trial_link,
                'description' => $request->description,
                'updated_at' => now()
            ]);

        if ($updated) {
            return response()->json([
                'message' => 'Trial link and description set successfully.'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Failed to set trial link and description. Ensure the trial exists.'
            ], 404);
        }
    }

    // Method to get the trial link and description for a specific trial user
    public function getTrialLinkAndDescription($courseId, $userId)
    {
        $trialInfo = DB::table('trials')
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->select('trial_link', 'description')
            ->first();

        if ($trialInfo) {
            return response()->json([
                'trial_link' => $trialInfo->trial_link,
                'description' => $trialInfo->description
            ], 200);
        } else {
            return response()->json([
                'message' => 'Trial information not found for this user and course.'
            ], 404);
        }
    }
}
