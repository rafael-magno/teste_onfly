<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller
{
    public function respondSuccess(
        JsonResource $resource,
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        return $resource->response()->setStatusCode($statusCode);
    }

    public function respondError(
        string $message,
        int $statusCode = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        return response()->json(['message' => $message], $statusCode);
    }
}
