<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InstructorPayment;

class InstructorPayrollController extends Controller
{
    public function createPayroll (Request $request) {
        try {
            // $userId = auth()->id();
            if (!auth()->id()) {
                return response()->json(['message'=>'You are not authorized.'], 401);
            }

            $validated = $request->validate([
                'instructor' => 'required',
                'no_of_classes' => 'required|integer',
                'per_class_payment' => 'required|numeric',
            ], [
                'instructor.required' => 'Select one instructor.',
                'no_of_classes.required' => 'No of Classes is required.',
                'no_of_classes.integer' => 'No of Classes must be a number.',
                'per_class_payment.required' => 'Per class payment rate is required.',
                'per_class_payment.numeric' => 'Payment should be in number.'
            ]);

            $total = $validated['no_of_classes'] * $validated['per_class_payment'];

            $payment = InstructorPayment::create([
                'instructor_id' => $validated['instructor'],
                'no_of_classes' => $validated['no_of_classes'],
                'per_class_payment' => $validated['per_class_payment'],
                'transaction' => $request['transaction'],
                'total_amount' => $total,
                'group_student_name' => $request['group_student_name'],
                'month' => $request['month'],
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

    public function getAllPayroll () {
        try {
            //code...
            if (!auth()->id()) {
                return response()->json(['message'=>'You are not authorized.'], 401);
            }

            // Fetch distinct instructor payments with user details
            $myPayments = InstructorPayment::with(['user' => function($query) {
                $query->select('id', 'name', 'phone', 'email'); // Select only the necessary fields
            }])
                        ->select('instructor_id', DB::raw('MAX(created_at) as last_payment_date')) // Get the last payment date
                        ->groupBy('instructor_id')
                        ->get();

            // Prepare the response data
            $payments = $myPayments->map(function($payment) {
                return [
                    'instructor_id' => $payment->instructor_id,
                    'name' => $payment->user->name,
                    'phone' => $payment->user->phone,
                    'email' => $payment->user->email,
                    'last_payment_date' => $payment->last_payment_date // Use the last payment date from the query
                ];
            });

            return response()->json([
                'payments' => $payments
            ], 200);

        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Payroll Error: ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }

    public function getMyPayroll () {
        try {
            //code...
            if (!auth()->id()) {
                return response()->json(['message'=>'You are not authorized.'], 401);
            }

            $myPayments = InstructorPayment::where('instructor_id', auth()->id())->orderBy('created_at', 'desc')->get();

            return response()->json([
                'payments' => $myPayments
            ], 200);

        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Payroll Error: ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }
}


























