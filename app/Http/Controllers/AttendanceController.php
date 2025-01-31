<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\GroupUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // You can return all attendance records or paginate them
        $attendances = Attendance::all();
        return response()->json($attendances);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request){
        try {
            // Validate the incoming request data
            // $validated = $request->validate([
            //     'attendances' => 'required|array', // Ensure 'attendances' is an array
            //     'attendances.*.student_id' => 'required|integer', // Validate each student_id
            //     'attendances.*.course_id'  => 'required|integer', // Validate each course_id
            //     'attendances.*.group_id'   => 'required|integer', // Validate each group_id
            //     'attendances.*.class_code' => 'required|string', // Validate each class_code
            // ]);

            // // Process each attendance record
            // foreach ($validated['attendances'] as $attendanceData) {
            //     // Create the attendance record
            //     Attendance::create([
            //         'user_id' => $attendanceData['student_id'],
            //         'course_id'  => $attendanceData['course_id'],
            //         'group_id'   => $attendanceData['group_id'],
            //         'class_code' => $attendanceData['class_code'],
            //     ]);
            // }
            $validated = $request->validate([
                'course_id' => 'required',
                'class_code' => 'required',
                'group_id' => 'required',
            ]);

            if (!Attendance::where('user_id', auth()->id())
                ->where('class_code', $request['class_code'])
                // ->whereDate('created_at', Carbon::today()) // Check if entry is today
                ->exists()) {
                // If no attendance entry exists for today, create a new one
                Attendance::create([
                    'user_id' => auth()->id(),
                    'course_id' => $request['course_id'],
                    'group_id' => $request['group_id'],
                    'class_code' => $request['class_code'],
                ]);

                $classCounter = GroupUser::where('user_id', auth()->id())->first();
                // $classCounter->class_counted = $classCounter->class_counted + 1;
                $classCounter->increment('class_counted');
                $classCounter->save();
            }


            return response()->json(['message' => 'Attendance records created successfully.'], 201);

        } catch (\Throwable $e) {
            // Handle the exception (log it, return a response, etc.)
            return response()->json(['error' => 'An error occurred while processing the request.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attendance $attendance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendance)
    {
        //
    }
}
