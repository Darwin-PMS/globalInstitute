<?php

namespace App\Http\Controllers\Api\OnlineLearning\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Standard Online Learning JSON envelope.
 *
 * Success: { success: true,  message, data, meta }
 * Failure: { success: false, message, errors }
 */
trait ApiResponses
{
    protected function ok(mixed $data = null, string $message = '', array $meta = []): JsonResponse
    {
        $payload = ['success' => true, 'message' => $message, 'data' => $data];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload);
    }

    /**
     * Envelope a paginator: items in `data`, page info in `meta`.
     */
    protected function paginated(LengthAwarePaginator $paginator, string $message = ''): JsonResponse
    {
        return $this->ok($paginator->items(), $message, [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ]);
    }

    protected function fail(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    protected function forbidden(string $message = 'You do not have permission to access this content.'): JsonResponse
    {
        return $this->fail($message, 403);
    }

    protected function notFound(string $message = 'The requested resource could not be found.'): JsonResponse
    {
        return $this->fail($message, 404);
    }

    /**
     * Clamp a client-supplied page size so a caller cannot request the whole table.
     */
    protected function perPage(mixed $requested, int $default = 15, int $max = 100): int
    {
        $value = (int) ($requested ?: $default);

        return max(1, min($value, $max));
    }
}
