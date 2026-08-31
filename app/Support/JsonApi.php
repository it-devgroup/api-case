<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class JsonApi
{
    /**
     * Build a single-error JSON:API document.
     */
    public static function error(string $status, string $title, ?string $detail = null, ?string $pointer = null, ?string $code = null): JsonResponse
    {
        return static::errors([static::errorObject($status, $title, $detail, $pointer, $code)], (int) $status);
    }

    /**
     * Build a multi-error JSON:API document.
     *
     * @param  array<int, array<string, mixed>>  $errors
     */
    public static function errors(array $errors, int $status): JsonResponse
    {
        return response()->json(['errors' => $errors], $status)
            ->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Build a JSON:API error object (a member of the top-level "errors" array).
     *
     * @return array<string, mixed>
     */
    public static function errorObject(string $status, string $title, ?string $detail = null, ?string $pointer = null, ?string $code = null): array
    {
        $error = ['status' => $status, 'title' => $title];

        if ($code !== null) {
            $error['code'] = $code;
        }

        if ($detail !== null) {
            $error['detail'] = $detail;
        }

        if ($pointer !== null) {
            $error['source'] = ['pointer' => $pointer];
        }

        return $error;
    }

    /**
     * Build a JSON:API document containing only top-level "meta", no "data".
     *
     * @param  array<string, mixed>  $meta
     */
    public static function meta(array $meta, int $status = 200): JsonResponse
    {
        return response()->json(['meta' => $meta], $status)
            ->header('Content-Type', 'application/vnd.api+json');
    }
}
