<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\InstructorPayment;
use App\Models\InstructorPaymentDetail;

class InstructorPayrollController extends Controller
{
    public function createPayroll (Request $request) {
        try {
            if (!auth()->id()) {
                return response()->json(['message'=>'You are not authorized.'], 401);
            }

            $validated = $request->validate([
                'instructor' => 'required',
                'no_of_classes' => 'required|integer',
                'per_class_payment' => 'required|numeric',
                'month' => 'required|integer',
                'year' => 'required|integer',
                'group_student_name' => 'required|string', // Validate group_student_name
                'transaction' => 'required|string', // Validate transaction
            ], [
                'instructor.required' => 'Select one instructor.',
                'no_of_classes.required' => 'No of Classes is required.',
                'no_of_classes.integer' => 'No of Classes must be a number.',
                'per_class_payment.required' => 'Per class payment rate is required.',
                'per_class_payment.numeric' => 'Payment should be in number.',
                'month.required' => 'Select one month.',
                'group_student_name.required' => 'Group student name is required.',
                'transaction.required' => 'Transaction details are required.',
            ]);

            $total = $validated['no_of_classes'] * $validated['per_class_payment'];

            // $instructorPaymentExists = InstructorPayment::where('instructor_id', $validated['instructor'])
            //                                         ->where('month', $validated['month'])
            //                                         ->where('year', $validated['year'])
            //                                         ->exists();

            $payment = InstructorPayment::where('instructor_id', $validated['instructor'])
                                                    ->where('month', $validated['month'])
                                                    ->where('year', $validated['year'])
                                                    ->first();

            if (!$payment) {
                $newPayment = InstructorPayment::create([
                    'instructor_id' => $validated['instructor'],
                    'total_amount' => $total,
                    'no_of_classes' => $validated['no_of_classes'],
                    'month' => $validated['month'],
                    'year' => $validated['year'],
                ]);

                $details = InstructorPaymentDetail::create([
                    'instructor_payment_id' => $newPayment->id, // Fixed typo here
                    'group_student_name' => $validated['group_student_name'],
                    'no_of_classes' => $validated['no_of_classes'],
                    'per_class_payment' => $validated['per_class_payment'],
                    'total_amount' => $total,
                    'transaction' => $validated['transaction'],
                ]);
            } else {
                $payment->total_amount += $total; // Update total amount
                $payment->no_of_classes += $validated['no_of_classes']; // Update total amount
                $payment->save();

                // Create payment detail
                $details = InstructorPaymentDetail::create([
                    'instructor_payment_id' => $payment->id, // Fixed typo here
                    'group_student_name' => $validated['group_student_name'],
                    'no_of_classes' => $validated['no_of_classes'],
                    'per_class_payment' => $validated['per_class_payment'],
                    'total_amount' => $total,
                    'transaction' => $validated['transaction'],
                ]);
            }
                                  
            $key = 'all_payrolls';
            Cache::forget($key);

            return response()->json([
                'message' => 'Payment successful',
                'payment' => InstructorPayment::all(), 
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
            $key = 'all_payrolls';
            if (!auth()->id()) {
                return response()->json(['message'=>'You are not authorized.'], 401);
            }

            if (Cache::has($key)) {
                $payments = json_decode(Cache::get($key), true); // Decode the JSON data
                return response()->json([
                    'payments' => $payments
                ], 200);
            }

            $payments = InstructorPayment::with('user')->orderBy('year', 'desc')->get();

            Cache::put($key, $payments->toJson(), now()->addMinutes(13));

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

            $myPayments = InstructorPayment::where('instructor_id', auth()->id())->orderBy('year', 'desc')->get();

            return response()->json([
                'payments' => $myPayments
            ], 200);

        } catch (\Throwable $e) {
            Log::error("Payroll Error: ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }

    public function getPayrollByInstructorId ($instructorId) {
        try {
            //code...
            if (!$instructorId) {
                return response()->json(['message'=>'You are not authorized.'], 401);
            }
            $myPayments = InstructorPayment::where('instructor_id', $instructorId)->orderBy('year', 'desc')->get();
            return response()->json([
                'payments' => $myPayments
            ], 200);
        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Payroll Error: ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }

    public function getPayrollDetails($payrollId) {
        try {
            $payments = InstructorPaymentDetail::where('instructor_payment_id', $payrollId)->get();
            return response()->json([
                'payments' => $payments
            ], 200);
        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Payroll Error: ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }
    }
}


























