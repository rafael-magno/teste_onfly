<?php

namespace App\Services;

use App\Models\TravelOrder;
use App\Models\TravelOrderStatusHistory;
use Illuminate\Support\Facades\DB;

class TravelOrderService
{
    public function create(array $data): TravelOrder
    {
        return DB::transaction(function () use ($data) {
            $travelOrder = TravelOrder::create($data);

            $this->recordHistory($travelOrder, $data['user_id']);

            return $travelOrder;
        });
    }

    private function recordHistory(TravelOrder $travelOrder, int $userId, ?string $reason = null): void
    {
        TravelOrderStatusHistory::create([
            'travel_order_id' => $travelOrder->id,
            'user_id' => $userId,
            'status' => $travelOrder->status,
            'reason' => $reason,
            'created_at' => $travelOrder->created_at,
        ]);
    }
}
