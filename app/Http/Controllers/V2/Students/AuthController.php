<?php

namespace App\Http\Controllers\V2\Students;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuthController extends Controller
{
    /**
     * Students Login
     */
    public function studentLogin (Request $request) {
        try {
            $validated = $request->validate([

            ]);
            // begin transaction
            DB::beginTransaction();
            // write create operation
            
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->respondValidationFailed('Validation failed', 422, $e->errors());
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->respondNotFound('Model not found');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error: " . $e->getMessage());
            return $this->respondInternalServerError();
        }
    }

    /**
     * Students Register
     */

    /**
     * Students forgot password
     */
}
