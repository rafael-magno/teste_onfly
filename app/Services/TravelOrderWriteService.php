<?php

namespace App\Services;

use App\Enums\TravelOrderStatus;
use App\Events\TravelOrderStatusRecorded;
use App\Exceptions\InvalidTravelOrderStatusTransitionException;
use App\Models\TravelOrder;
use App\Models\TravelOrderStatusHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TravelOrderWriteService
{
    public function create(array $data): TravelOrder
    {
        return DB::transaction(function () use ($data) {
            $travelOrder = TravelOrder::create([
                ...$data,
                'user_id' => Auth::id(),
            ]);

            $this->recordHistory($travelOrder);

            return $travelOrder;
        });
    }

    public function updateStatus(
        int $travelOrderId,
        TravelOrderStatus $status,
        ?string $reason = null,
    ): TravelOrder {
        return DB::transaction(function () use ($travelOrderId, $status, $reason) {
            $travelOrder = TravelOrder::findOrFail($travelOrderId);

            if ($travelOrder->status !== TravelOrderStatus::Requested) {
                throw new InvalidTravelOrderStatusTransitionException(
                    "Não é possível alterar o status de um pedido com status: {$travelOrder->status->label()}."
                );
            }

            $travelOrder->update(['status' => $status]);

            $this->recordHistory($travelOrder, $reason);

            return $travelOrder;
        });
    }

    private function recordHistory(TravelOrder $travelOrder, ?string $reason = null): void
    {
        TravelOrderStatusHistory::create([
            'travel_order_id' => $travelOrder->id,
            'user_id' => Auth::id(),
            'status' => $travelOrder->status,
            'reason' => $reason,
        ]);

        TravelOrderStatusRecorded::dispatch($travelOrder);
    }
}
