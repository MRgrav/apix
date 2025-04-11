<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PreAssignerController extends Controller
{
    public function AssignStudent(Reuqest $request) {
        try {
            // purchases
            // payments
            // group_user
            $validated = $request->validate([
                'student_id' => 'required|integer|exist:users',
                'course_id' => 'required|integer',
                'plan_id' => 'required|integer',
                'class_frequency_id' => 'required|integer',
                'payment_id' => 'required|string',
                // 'duration' => 'nullable|integer|min:1',
                'expiry_date' => 'required|date',
                'number_of_classes' => 'required|integer',
                'class' => 'required|string',
                'total_classes' => 'required|integer',
                'class_counted' => 'required|integer',
                'category' => 'required|integer',
                'amount' => 'required|integer'
            ]);

            DB::beginTransaction();
            App\Models\Purchase::create([
                'user_id' => $validated['student_id'],
                'course_id' => $validated['course_id'],
                'payment_id' => $validated['payment_id'],
                'amount' => $validated['amount'],
                'plan_id' => $validated['plan_id'],
                'expiry_date' => $validated['expiry_date'],
                'number_of_classes' => $validated['number_of_classes'],
                'class_frequency_id' => $validated['class_frequency_id'],
                'class' => $validated['class'],
            ]);

            App\Models\GroupUser::create([
                'group_id' => $validated[''],
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
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error('Assign controller create: ' . $e->getMessage());
            return response()->json(['message' => 'Student Assign Failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function view() {
        try {
            // get students
            $students = App\Models\User::where('role_id', 1)->get();
            // get courses
            $courses = App\Models\Course::select('id','title')->with('category')->get();
            // get plans
            $plans = App\Models\Plan::get();
            // get class frequency
            $classFrequency = App\Models\ClassFrequency::get();
            
            return response()->json([
                'students' => $students,
                'courses' => $courses,
                'plans' => $plans,
                'classFrequency' => $classFrequency,
            ], 200);
            
        } catch (\Throwable $e) {
            Log::error('Assign controller view: ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error', 'error' => $e->getMessage()], 500);

        }
    }

}
