<?php

namespace App\Http\Controllers;

use App\Models\CoursePlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CoursePlanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $plans = CoursePlan::with('course')->get();
        return response()->json($plans);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'plan_name' => 'required|string|max:255',
            'old_rate' => 'nullable|numeric',
            'current_rate' => 'required|numeric',
            'category' => 'required|string|max:255',
            'is_NRI' => 'boolean',
            'GST' => 'nullable|numeric',
            'final_rate' => 'required|numeric',
        ]);

        $plan = CoursePlan::create($validatedData);

        return response()->json(['message' => 'Plan created successfully', 'plan' => $plan], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CoursePlan  $coursePlan
     * @return \Illuminate\Http\Response
     */
    public function show(CoursePlan $coursePlan)
    {
        return response()->json($coursePlan);
    }

    public function coursePlans($id){
        try {
            //code...

            // Fetch the logged-in user
            $user = Auth::user();

             // Log user information for debugging
            Log::debug("User: ", ['user' => $user]);

            // Ensure the user is authenticated
            if (!$user) {
                $plans = CoursePlan::where('category',$id)->get();
            } else {
                $plans = coursePlan::where('is_nri', $user->is_nri)->get();
            }

            return response()->json($plans, 200);
        } catch (\Throwable $e) {
            //throw $e;
            Log::error("Plan error: ". $e->getMessage());
            return response()->json(['message'=>'Something is wrong'], 200);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CoursePlan  $coursePlan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CoursePlan $coursePlan)
    {
        $validatedData = $request->validate([
            'plan_name' => 'string|max:255',
            'old_rate' => 'nullable|numeric',
            'current_rate' => 'numeric',
            'category' => 'string|max:255',
            'is_NRI' => 'boolean',
            'GST' => 'nullable|numeric',
            'final_rate' => 'numeric',
        ]);

        $coursePlan->update($validatedData);

        return response()->json(['message' => 'Plan updated successfully', 'plan' => $coursePlan]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CoursePlan  $coursePlan
     * @return \Illuminate\Http\Response
     */
    public function destroy(CoursePlan $coursePlan)
    {
        $coursePlan->delete();

        return response()->json(['message' => 'Plan deleted successfully']);
    }
    public function getPlansByNriStatus()
    {
        try {
            //code...
            // Fetch the logged-in user
            $user = Auth::user();

            // Ensure the user is authenticated
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated',
                ], 401);
            }
            
            $plans = coursePlan::where('is_nri', $user->is_NRI)->get();

            // Return the plans as a JSON response
            return response()->json([
                'status' => 'success',
                'plans' => $plans,
            ], 200);
        } catch (\Throwable $e) {
            //throw $e;
            Log::error("Plan error: ". $e->getMessage());
            return response()->json(['message'=>'Something is wrong'], 200);
        }
    }
}
