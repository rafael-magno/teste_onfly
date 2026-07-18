<?php

namespace App\Http\Controllers;

use App\Enums\TravelOrderStatus;
use App\Exceptions\InvalidTravelOrderStatusTransitionException;
use App\Http\Requests\StoreTravelOrderRequest;
use App\Http\Requests\UpdateTravelOrderStatusRequest;
use App\Http\Resources\TravelOrderResource;
use App\Models\TravelOrder;
use App\Services\TravelOrderService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TravelOrderController extends Controller
{
    public function __construct(private readonly TravelOrderService $travelOrderService)
    {
    }

    public function store(StoreTravelOrderRequest $request): JsonResponse
    {
        $travelOrder = $this->travelOrderService->create($request->validated());

        return $this->respondSuccess(
            TravelOrderResource::make($travelOrder),
            Response::HTTP_CREATED
        );
    }

    public function updateStatus(UpdateTravelOrderStatusRequest $request, TravelOrder $travelOrder): JsonResponse
    {
        try {
            $travelOrder = $this->travelOrderService->updateStatus(
                $travelOrder,
                TravelOrderStatus::from($request->status),
                $request->reason ?? null,
            );
        } catch (InvalidTravelOrderStatusTransitionException $e) {
            return $this->respondError($e->getMessage(), Response::HTTP_CONFLICT);
        }

        return $this->respondSuccess(TravelOrderResource::make($travelOrder));
    }
}
