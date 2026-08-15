<?php

namespace App\Base;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

class ApiResponse
{
    public static function success(mixed $data = [], string $message = 'Success', int $code = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        $paginator = null;

        if ($data instanceof AbstractPaginator) {
            $paginator = $data;
            $payload['data'] = $data->items();
        } elseif ($data instanceof ResourceCollection && $data->resource instanceof AbstractPaginator) {
            $paginator = $data->resource;
        }

        if ($paginator) {
            $payload['links'] = [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ];

            $payload['meta'] = [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'links' => $paginator->linkCollection()->toArray(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ];
        }

        return response()->json($payload, $code);
    }

    public static function error(string $message = 'Error', int $code = 400, mixed $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }
}
