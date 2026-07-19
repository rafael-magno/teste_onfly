<?php

namespace App\Http\Controllers;

use App\Enums\TravelOrderStatus;
use App\Exceptions\InvalidTravelOrderStatusTransitionException;
use App\Http\Requests\IndexTravelOrderRequest;
use App\Http\Requests\StoreTravelOrderRequest;
use App\Http\Requests\UpdateTravelOrderStatusRequest;
use App\Http\Resources\TravelOrderResource;
use App\Models\TravelOrder;
use App\Services\TravelOrderReadService;
use App\Services\TravelOrderWriteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TravelOrderController extends Controller
{
    public function __construct(
        private readonly TravelOrderWriteService $travelOrderWriteService,
        private readonly TravelOrderReadService $travelOrderReadService,
    ) {
    }

    public function index(IndexTravelOrderRequest $request): JsonResponse
    {
        $travelOrders = $this->travelOrderReadService->list($request->validated());

        return $this->respondSuccess(TravelOrderResource::collection($travelOrders));
    }

    public function store(StoreTravelOrderRequest $request): JsonResponse
    {
        $travelOrder = $this->travelOrderWriteService->create($request->validated());

        return $this->respondSuccess(
            TravelOrderResource::make($travelOrder),
            Response::HTTP_CREATED
        );
    }

    public function updateStatus(UpdateTravelOrderStatusRequest $request, int $travelOrder): JsonResponse
    {
        try {
            $travelOrder = $this->travelOrderWriteService->updateStatus(
                $travelOrder,
                TravelOrderStatus::from($request->status),
                $request->reason ?? null,
            );
        } catch (InvalidTravelOrderStatusTransitionException $e) {
            return $this->respondError($e->getMessage(), Response::HTTP_CONFLICT);
        } catch (ModelNotFoundException $e) {
            return $this->respondError("Pedido de viagem não encontrado.", Response::HTTP_NOT_FOUND);
        }

        return $this->respondSuccess(TravelOrderResource::make($travelOrder));
    }
}
