<?php

namespace App\Traits;

trait ApiReturnFormatTrait
{
    public function responseWithSuccess($message, $data = [], $statusCode = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public function responseWithError($message, $data = [], $statusCode = 400)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}
