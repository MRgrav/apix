<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\ClassFrequency;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PreAssignerController extends Controller
{
    public function AssignStudent(Request $request) {
        try {
            $validated = $request->validate([
                'student_id' => 'required|integer|exists:users,id',
                'course_id' => 'required|integer',
                'plan_id' => 'required|integer',
                'class_frequency_id' => 'required|integer',
                'payment_id' => 'required|string',
                'expiry_date' => 'required|date',
                'class' => 'required|string',
                'total_classes' => 'required|integer',
                'class_counted' => 'required|integer',
                'category' => 'nullable|integer',
                'amount' => 'required|integer',
                'group_id' => 'required|integer|exists:groups,id'
            ]);

            DB::beginTransaction();
            Purchase::create([
                'user_id' => $validated['student_id'],
                'course_id' => $validated['course_id'],
                'payment_id' => $validated['payment_id'],
                'status' => 'active',
                'amount' => $validated['amount'],
                'plan_id' => $validated['plan_id'],
                'expiry_date' => $validated['expiry_date'],
                'number_of_classes' => $validated['total_classes'],
                'class_frequency_id' => $validated['class_frequency_id'],
                'class' => $validated['class'],
            ]);

            GroupUser::create([
                'group_id' => $validated['group_id'],
                'user_id' => $validated['student_id'],
                'course_id' => $validated['course_id'],
                'expiry_date' => $validated['expiry_date'],
                'plan_id' => $validated['plan_id'],
                'class_counted' => $validated['class_counted'],
                'total_classes' => $validated['total_classes'],
                'class' => $validated['class'],
                'category' => $validated['category'],
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error('Assign controller create: ' . $e->getMessage());
            return response()->json(['message' => 'Student Assign Failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function view(){
        try {
            $perPage = request('per_page', 10);
            $page = request('page', 1);

            // Get data
            $students = User::where('role_id', 1)->get();
            $courses = Course::with('category')->select('id', 'title')->get();
            $plans = CoursePlan::get();
            $classFrequency = ClassFrequency::get();
            $groups = Group::get();

            // Fetch group user
            $purchases = GroupUser::with(['plan','course','group','user'])
                ->paginate($perPage);

            $purchases->appends(['page' => $page, 'per_page' => $perPage]);

            // Return response
            return response()->json([
                'students' => $students,
                'courses' => $courses,
                'plans' => $plans,
                'classFrequency' => $classFrequency,
                'groups' => $groups,
                'purchases' => $purchases,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Assign controller view: ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }


    public function getById($id){
        try {

            // Get data
            $students = User::where('role_id', 1)->get();
            $courses = Course::with('category')->select('id', 'title')->get();
            $plans = CoursePlan::get();
            $classFrequency = ClassFrequency::get();
            $groups = Group::get();

            // Fetch group user
            $purchases = GroupUser::with(['plan','course','group','user'])->findOrFail($id);

            // Return response
            return response()->json([
                'students' => $students,
                'courses' => $courses,
                'plans' => $plans,
                'classFrequency' => $classFrequency,
                'groups' => $groups,
                'purchases' => $purchases,
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Assign controller view: ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    public function update(Request $request, $groupId){
        try {
            $validated = $request->validate([
                'student_id' => 'nullable|integer|exists:users,id',
                'course_id' => 'nullable|integer',
                'plan_id' => 'nullable|integer',
                'class_frequency_id' => 'nullable|integer',
                'payment_id' => 'nullable|string',
                'expiry_date' => 'nullable|date',
                'class' => 'nullable|string',
                'total_classes' => 'nullable|integer',
                'class_counted' => 'nullable|integer',
                'category' => 'nullable|integer',
                'amount' => 'nullable|integer',
            ]);

            DB::beginTransaction();
            $groupUser = GroupUser::where('group_id', $groupId)->first();

            if (!$groupUser) {
                return response()->json(['message' => 'Group not found'], 404);
            }

            $purchase = Purchase::where('user_id', $groupUser->user_id)->first();

            if (!$purchase) {
                return response()->json(['message' => 'Purchase not found for student'], 404);
            }

            $purchaseData = [];
            $groupUserData = [];

            foreach ($validated as $key => $value) {
                if ($value !== null) {
                    if ($purchase->{$key}) {
                        $purchaseData[$key] = $value;
                    }
                    if ($groupUser->{$key}) {
                        $groupUserData[$key] = $value;
                    }
                }
            }

            $purchase->update($purchaseData);
            $groupUser->update($groupUserData);

            DB::commit();
            return response()->json(['message' => 'Student updated successfully'], 200);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::error('Assign controller update: ' . $e->getMessage());
            return response()->json(['message' => 'Student update failed', 'error' => $e->getMessage()], 500);
        }
    }


}
