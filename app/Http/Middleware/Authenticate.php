<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // return $request->expectsJson() ? null : route('login');

        // If the request expects JSON (API), return a 401 Unauthorized response
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // If the request does not expect JSON (i.e., it's a web request), proceed with the usual behavior
        return route('login');
    }
}
