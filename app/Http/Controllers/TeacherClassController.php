<?php

namespace App\Http\Controllers;

use App\Models\TeacherClass;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TeacherClassController extends Controller
{
    /**
     * Display a listing of the resource.
     * Redis : done
     */
    public function index()
    {
        // List all TeacherClass records, or use pagination if needed
        $key = 'allTeacherClasses';

        if (Cache::has($key)) {
            $teacherClasses = json_decode(Cache::get($key), true); // Decode the JSON data
            return response()->json([
                'message' => 'Fetched teacher classes,',
                'courses' => $teacherClasses
            ], 200);
        }  

        Cache::put($key, $teacherClasses->toJson(), now()->addMinutes(18));

        $teacherClasses = TeacherClass::all();
        return response()->json([
            'message' => 'Fetched teacher classes,',
            'courses' => $teacherClasses
        ], 200);
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
    public function store(Request $request)
    {
        // creates teacher attendance and class code
        $codeString = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        try {
            // Get the authenticated user's ID
            $userId = auth()->id();
    
            // Validate the incoming request data
            $validated = $request->validate([
                'group_id' => 'required|integer',
            ]);
    
            // Get the current date using Carbon
            $currentDate = Carbon::now();
    
            // Generate the class code using the group ID and formatted date
            $class_code = $validated['group_id'] . '&' .
                          $codeString[$currentDate->year % 50] . // Year (mod 26 for index)
                          $codeString[$currentDate->month - 1] . // Month (0-indexed)
                          $codeString[$currentDate->day - 1]; // Day (0-indexed)
    
            // Create the TeacherClass record
            TeacherClass::create([
                'user_id' => $userId,
                'group_id' => $validated['group_id'],
                'class_code' => $class_code,
            ]);

            $key = 'group_details_' . $validated['group_id'];
            Cache::forget($key);
    
            // Return a success response
            return response()->json(['message' => 'Teacher attendance created successfully.'], 201);
    
        } catch (\Throwable $e) {
            // Log the error for debugging
            Log::error('Error creating teacher attendance: ' . $e->getMessage());

            // Return a meaningful error response
            return response()->json(['error' => 'An error occurred while creating attendance.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TeacherClass $teacherClass)
    {
        // Return the specific TeacherClass record
        return response()->json($teacherClass);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TeacherClass $teacherClass)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TeacherClass $teacherClass)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TeacherClass $teacherClass)
    {
        //
    }
}
