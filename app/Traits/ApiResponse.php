<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Reusable unified API response function for both success and error responses.
     */
    public function apiResponse(
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
