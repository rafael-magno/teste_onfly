<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTravelOrderRequest;
use App\Http\Resources\TravelOrderResource;
use App\Services\TravelOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TravelOrderController extends Controller
{
    public function __construct(private readonly TravelOrderService $travelOrderService)
    {
    }

    public function store(StoreTravelOrderRequest $request): JsonResponse
    {
        $travelOrder = $this->travelOrderService->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return TravelOrderResource::make($travelOrder)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
