<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\StudyMaterial;
use App\Models\TeacherClass;

class AdminController extends Controller
{
    //
    public function getAdminDashboard () {
        return response()->json("hello", 200);
    }

    public function getGroupDetails ($groupId) {
        try {
            //code...
            $key = 'group_details_' . $groupId; // Use $groupId instead of $id
            $studentsKey = 'group_students_' . $groupId;       // students enrolled under the group
            $materialsKey = 'group_materials' . $groupId;

            // Check if the group details are cached
            if (Cache::has($key)) {
                $group = json_decode(Cache::get($key), true); // Decode the JSON data
            } else {
                // Fetch the group with related data
                $group = Group::with(['users', 'course', 'videos', 'instructor'])->find($groupId);

                if (!$group) {
                    return response()->json(['message' => 'Group not found'], 404);
                }

                Cache::put($key, $group->toJson(), now()->addMinutes(2));
            }

            if (Cache::has($studentsKey)) {
                $students = json_decode(Cache::get($studentsKey), true); // Decode the JSON data
            } else {
                // Fetch the group user with related data
                $students = GroupUser::with('user')
                                    ->where('group_id', $groupId)
                                    ->get();
                Cache::put($studentsKey, $students->toJson(), now()->addMinutes(1));
            }

            if (Cache::has($materialsKey)) {
                $materials = json_decode(Cache::get($materials), true); // Decode the JSON data
            } else {
                $studyMaterials = StudyMaterial::where('group_id', $groupId)->get();
                Cache::put($materialsKey, $studyMaterials->toJson(), now()->addSeconds(5));
            }

            $classes = TeacherClass::where('group_id', $groupId)
                                    ->orderBy('created_at', 'desc')
                                    ->get();

            return response()->json([
                'message' => 'Group details',
                'details' => $group,
                'classes' => $classes,
                'students' => $students,
                'materials' => $materials
            ], 200);

        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Error in Admin Group Details : ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500,);
        }
    }
}
