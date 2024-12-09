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
            'age' => 'required|integer|min:1',
            'class' => 'required|string|max:50',
            'skill_level' => 'required|string|max:50',
            'subject' => 'required|string|max:100',
            'contact_number' => 'required|string|max:15',
            'whatsapp_number' => 'nullable|string|max:15',
            'email' => 'required|email|unique:students,email',
            'address' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::create($request->all());

        return response()->json([
            'message' => 'Student created successfully!',
            'student' => $student
        ], 201);
    }
    public function getAllStudents()
    {
        $students = Student::all();

        return response()->json([
            'message' => 'Students retrieved successfully!',
            'students' => $students
        ], 200);
    }
    
}
