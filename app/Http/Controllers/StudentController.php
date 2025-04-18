<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // 'age' => 'required|integer|min:1',
            // 'class' => 'required|string|max:50',
            // 'skill_level' => 'string|max:50',
            'subject' => 'required|string|max:100',
            'contact_number' => 'required|string|max:15',
            'whatsapp_number' => 'nullable|string|max:15',
            'email' => 'required|email',
            'address' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::create([
            'name' => $request->name,
            'age' => $request->age,
            'class' => $request->class,
            'skill_level' => $request->skill_level,
            'subject' => $request->subject,
            'contact_number' => $request->contact_number,
            'whatsapp_number' => $request->whatsapp_number,
            'email' => $request->email,
            'address' => $request->address,
            'plan_id' => $request->plan_id,
        ]);

        return response()->json([
            'message' => 'Student created successfully!',
            'student' => $student
        ], 201);
    }
    public function getAllStudents()
    {
        $students = Student::with('plan')->get();

        return response()->json([
            'message' => 'Students retrieved successfully!',
            'students' => $students
        ], 200);
    }
    
}
