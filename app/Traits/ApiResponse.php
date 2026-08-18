<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;

trait ApiResponse
{
    /**
     * Standardized success response
     */
    protected function successResponse($data, $message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data
        ], $code);
    }

    /**
     * Standardized error response with single string message
     */
    protected function errorResponse($message, $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => is_array($message) ? implode(', ', $message) : $message
        ], $code);
    }

    /**
     * Formats validation errors into a single comma-separated string
     */
    protected function formatValidationErrors($validator)
    {
        return implode(', ', $validator->errors()->all());
    }
}