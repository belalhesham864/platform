<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Reusable unified API response method for static contexts (e.g. exception handler).
     */
    public static function apiResponse(
        mixed $data = null,
        string $message = '',
        int $statusCode = 200,
        mixed $errors = null,
        ?bool $status = null
    ): JsonResponse {
        $status = $status ?? ($statusCode >= 200 && $statusCode < 400);

        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data ?? (object) [],
            'errors' => $errors,
        ], $statusCode);
    }
}
