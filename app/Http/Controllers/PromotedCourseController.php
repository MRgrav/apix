<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShowcaseCourse;
use Illuminate\Support\Facades\Log;

class PromotedCourseController extends Controller
{
    // Create a new promotion
    public function createPromotion(Request $request) {
        try {
            //code...
            $validator = $request->validate([
                'course_id' => 'required|exists:courses,id',
                // 'status' => 'boolean',
                'display_order' => 'nullable|integer',
            ]); 
            $promotion = ShowcaseCourse::create([
                'course_id' => $validator['course_id'], 
                'status' => true, 
                'display_order' => $validator['display_order'],
            ]);

            return response()->json($promotion, 201);
        } catch (\Throwable $e) {
            //throw $th;
            Log::error("Create: ". $e->getMessage());
            return response()->json(['message' => 'internal server error'], 500);
        }       
    }

    // Retrieve all promotions
    public function getAllPromotions() {
        $all = ShowcaseCourse::with('course')
                        ->orderByRaw('COALESCE(display_order, 999999) ASC')    // null into large number
                        ->get();
        return response()->json($all, 200);
    }

    // Retrieve active promotions
    public function getActivePromotions() {
        $activePromotions = ShowcaseCourse::with('course')
            ->where('status', true)
            ->orderByRaw('COALESCE(display_order, 999999) ASC') // Order by display_order, null replaced with large number
            ->get();

        return response()->json($activePromotions, 200);
    }

    // Update a promotion
    public function updatePromotion(Request $request, $promotionId) {
        $promotion = ShowcaseCourse::find($promotionId);

        if (!$promotion) {
            return response()->json(['message' => 'Promotion not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'course_id' => 'sometimes|exists:courses,id',
            'status' => 'sometimes|boolean',
            'display_order' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $promotion->update($request->only(['course_id', 'status', 'display_order']));
        return response()->json($promotion, 200);
    }

    // Delete a promotion
    public function deletePromotion($promotionId) {
        $promotion = ShowcaseCourse::find($promotionId);

        if (!$promotion) {
            return response()->json(['message' => 'Promotion not found'], 404);
        }

        $promotion->delete();
        return response()->json(['message' => 'Promotion deleted successfully'], 200);
    }

    // Activate/Deactivate a promotion
    public function togglePromotionStatus($promotionId) {
        $promotion = ShowcaseCourse::find($promotionId);

        if (!$promotion) {
            return response()->json(['message' => 'Promotion not found'], 404);
        }

        $promotion->status = !$promotion->status;
        $promotion->save();

        return response()->json($promotion, 200);
    }

    // Set display order
    public function setDisplayOrder($promotionId, $order) {
        $promotion = ShowcaseCourse::find($promotionId);

        if (!$promotion) {
            return response()->json(['message' => 'Promotion not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'display_order' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $promotion->display_order = $request->display_order;
        $promotion->save();

        return response()->json($promotion, 200);
    }

}
