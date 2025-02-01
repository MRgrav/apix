<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\StudyMaterial;
use App\Models\TeacherClass;
use App\Models\InstructorPayment;
use App\Models\Routine;

class AdminController extends Controller
{
    //
    public function getAdminDashboard () {
        return response()->json("hello", 200);
    }

    public function getGroupDetails ($groupId) {
        try {
            //code...
            $routine = Routine::where('group_id', $groupId)->first();

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
                Cache::put($studentsKey, $students->toJson(), now()->addSeconds(10));
            }

            if (Cache::has($materialsKey)) {
                $materials = json_decode(Cache::get($materials), true); // Decode the JSON data
            } else {
                $materials = StudyMaterial::where('group_id', $groupId)->get();
                Cache::put($materialsKey, $materials->toJson(), now()->addSeconds(5));
            }

            $classes = TeacherClass::where('group_id', $groupId)
                                    ->orderBy('created_at', 'desc')
                                    ->get();

            return response()->json([
                'message' => 'Group details',
                'details' => $group,
                'classes' => $classes,
                'students' => $students,
                'materials' => $materials,
                'routine' => $routine || null
            ], 200);

        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Error in Admin Group Details : ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500,);
        }
    }

    public function createPayroll (Request $request) {
        try {
            //code...
            $userId = auth()->id();
            if (!$userId) {
                return response()->json(['message'=>'You are not authorized.'], 401);
            }

            $validated = $request->validate([
                'instructor' => 'required',
                'group_id' => 'required',
                'no_of_classes' => 'required|integer',
                'per_class_payment' => 'required|numeric',
                'transaction' => 'required|string',
            ]);

            $total = $validated['no_of_classes'] * $validated['per_class_payment'];

            $payment = InstructorPayment::create([
                'instructor_id' => $validated['instructor'],
                'group_id' => $validated['group_id'],
                'no_of_classes' => $validated['no_of_classes'],
                'per_class_payment' => $validated['per_class_payment'],
                'transaction' => $validated['transaction'],
                'total_amount' => $total,
            ]);

            return response()->json([
                'message' => 'Payment successful'
            ], 201);

        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Payroll Error: ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }

    public function createRoutine(Request $request, $groupId)
    {
        try {
            $validated = $request->validate([
                'instructor_id' => 'required',
                'day' => 'required',
                'time' => 'required',
            ]);

            $dayMap = [
                'sunday' => 'sun',
                'monday' => 'mon',
                'tuesday' => 'tue',
                'wednesday' => 'wed',
                'thursday' => 'thu',
                'friday' => 'fri',
                'saturday' => 'sat',
            ];

            $routine = Routine::firstOrCreate(
                ['group_id' => $groupId],
                [
                    'instructor_id' => $validated['instructor_id'],
                    'session' => null,
                ]
            );

            $routine->{$dayMap[$validated['day']]} = $validated['time'];
            $routine->save();

            Log::info('Class time added: ' . $validated['day'] . ' : ' . $validated['time']);

            return response()->json(['message' => 'Class time added'], 201);
        } catch (ModelNotFoundException $e) {
            Log::error("Routine not found for group ID: " . $groupId);
            return response()->json(['message' => 'Routine not found'], 404);
        } catch (\Throwable $e) {
            Log::error("Create routine error: " . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }


    // danger : no soft delete
    public function deleteRoutine($id) {
        try {
            //code...
            $routine = Routine::findOrFail($id);
            $routine->delete();

            return response()->json(['message' => 'Time deleted'], 200);
        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Routine Deletion: ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }
}
