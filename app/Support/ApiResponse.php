<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Unified API response shape used by every controller.
 */
trait ApiResponse
{
    protected function success(mixed $data = null, ?string $messageKey = null, int $status = 200, array $params = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $messageKey ? __($messageKey, $params) : null,
            'data' => $data,
        ], $status);
    }

    protected function paginated(mixed $paginator, ?string $messageKey = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $messageKey ? __($messageKey) : null,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    protected function error(string $messageKey, string $code, int $status = 400, array $params = [], mixed $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => __($messageKey, $params),
            'code' => $code,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
