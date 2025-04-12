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
            $purchases = GroupUser::leftJoin('purchases', 'purchases.user_id', '=', 'group_users.user_id')
                ->select('purchases.*', 'group_users.group_id', 'group_users.course_id')
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

}
