<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class DemoClassController extends Controller
{
    /**
     * Create a new demo class for a user
     */
    public function create(Request $request) {
        try {
            $validated = $request->validate([
                'phone' => 'required|digits:10',
                'class_url' => 'required|url',
                'class_time' => 'required|date', // Changed from timestamp
            ]);

            $user = User::where('phone', $validated['phone'])->firstOrFail();

            DB::beginTransaction();
            $user->update([
                'demo_class_url' => $validated['class_url'],
                'is_demo_active' => true,
                'demo_class_time' => $validated['class_time'],
            ]);
            DB::commit();

            return response()->json([
                'message' => 'Demo class created successfully',
                'user' => $user
            ], 201);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Student not found',
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update demo class details
     */
    public function update(Request $request) {
        try {
            $validated = $request->validate([
                'phone' => 'required|digits:10',
                'class_url' => 'sometimes|url',
                'class_time' => 'sometimes|date',
                'is_demo_active' => 'sometimes|boolean'
            ]);

            $user = User::where('phone', $validated['phone'])->firstOrFail();

            DB::beginTransaction();
            
            // Selectively update fields that are present in the request
            $updateData = [];
            if (isset($validated['class_url'])) {
                $updateData['demo_class_url'] = $validated['class_url'];
            }
            if (isset($validated['class_time'])) {
                $updateData['demo_class_time'] = $validated['class_time'];
            }
            if (isset($validated['is_demo_active'])) {
                $updateData['is_demo_active'] = $validated['is_demo_active'];
            }

            $user->update($updateData);
            DB::commit();

            return response()->json([
                'message' => 'Demo class updated successfully',
                'user' => $user
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Student not found',
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retrieve all users with demo classes
     */
    public function getAll() {
        try {
            $users = User::where('is_demo_active', true)->get();

            return response()->json([
                'message' => 'Students with demo classes fetched successfully',
                'students' => $users,
                'total' => $users->count()
            ], 200);

        } catch (\Throwable $e){
            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retrieve specific demo class details
     */
    public function getByPhone($phone) {
        try {
            if (!preg_match('/^\d{10}$/', $phone)) {
                return response()->json([
                    'message' => 'Invalid phone number format',
                    'errors' => ['phone' => 'Phone number must be 10 digits']
                ], 422);
            }

            $user = User::where('phone', $phone)
                ->where('is_demo_active', true)
                ->firstOrFail();

            return response()->json([
                'message' => 'Student demo class details retrieved',
                'student' => [
                    'phone' => $user->phone,
                    'demo_class_url' => $user->demo_class_url,
                    'demo_class_time' => $user->demo_class_time
                ]
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'No active demo class found for this student',
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }
    
    /**
     * Cancel or deactivate a demo class
     */
    public function cancel(Request $request) {
        try {
            $validated = $request->validate([
                'phone' => 'required|digits:10',
                'reason' => 'sometimes|string|max:255'
            ]);

            $user = User::where('phone', $validated['phone'])
                ->where('is_demo_active', true)
                ->firstOrFail();

            DB::beginTransaction();
            $user->update([
                'is_demo_active' => false,
                'demo_class_url' => null,
                'demo_class_time' => null,
                'demo_cancellation_reason' => $validated['reason'] ?? null
            ]);
            DB::commit();

            return response()->json([
                'message' => 'Demo class cancelled successfully',
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'No active demo class found for this student',
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
        /**
         * Get upcoming demo classes
         */
        // public function getUpcomingClasses() {
        //     try {
        //         $upcomingClasses = User::where('is_demo_active', true)
        //             ->where('demo_class_time', '>', now())
        //             ->orderBy('demo_class_time', 'asc')
        //             ->get();
    
        //         return response()->json([
        //             'message' => 'Upcoming demo classes retrieved',
        //             'classes' => $upcomingClasses,
        //             'total' => $upcomingClasses->count()
        //         ], 200);
    
        //     } catch (\Throwable $e) {
        //         return response()->json([
        //             'message' => 'Error retrieving upcoming classes',
        //             'error' => $e->getMessage()
        //         ], 500);
        //     }
        // }
}
    