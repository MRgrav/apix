<?php

namespace App\Http\Controllers;

use App\Models\Statuses;
use Illuminate\Http\Request;
use App\Models\Status;

class StatusController extends Controller
{
    /**
     * Retrieve all statuses.
     */
    public function index()
    {
        // Fetch all statuses
        $statuses = Statuses::all();

        // Return as JSON response
        return response()->json([
            'success' => true,
            'data' => $statuses,
        ], 200);
    }

    /**
     * Create a new status.
     */
    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Create a new status
        $status = Statuses::create($validated);

        // Return the created status
        return response()->json([
            'success' => true,
            'message' => 'Status created successfully.',
            'data' => $status,
        ], 201);
    }
}

