<?php

namespace App\Traits;

trait ApiResponseTrait
{
    public function respondSuccess($message = 'Success', $statusCode = 200, $data = null){
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public function respondError($message, $statusCode = 400, $data = null){
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /*
    +----------------------------+
    |   Response for 500 server  |
    +----------------------------+
    */
    public function respondInternalServerError($message = 'Internal Server Error', $statusCode = 500, $data = null){
        return $this->respondError($message, 500, $data);
    }

    /*
    +------------------------------+
    |   Response for unauthorized  |
    +------------------------------+
    */
    public function respondUnauthorized($message = 'Unauthorized', $statusCode = 401, $data = null){
        return $this->respondError($message, 401, $data);
    }

    /*
    +-----------------------------+
    |   Response for not found    |
    +-----------------------------+
    */
    public function respondNotFound($message = 'Not Found', $statusCode = 404, $data = null){
        return $this->respondError($message, 404, $data);
    }

    /*
    +-----------------------------------+
    |   Response for validation failed  |
    +-----------------------------------+
    */
    public function respondValidationFailed($message = 'Validation Failed', $statusCode = 422, $errors){
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }
}