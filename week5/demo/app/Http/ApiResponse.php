<?php

namespace App\Http;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'ok', int $status = 200): JsonResponse
    {
        return response()->json([
            'data'    => $data,
            'message' => $message,
        ], $status, [], JSON_UNESCAPED_UNICODE);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        $payload = ['data' => null, 'message' => $message];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status, [], JSON_UNESCAPED_UNICODE);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $resourceClass, string $message = 'ok'): JsonResponse
    {
        return response()->json([
            'data'    => $resourceClass::collection($paginator->items())->resolve(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
            'message' => $message,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
