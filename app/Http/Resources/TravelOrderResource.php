<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requester' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'destination_country' => $this->destination_country,
            'destination_state' => $this->destination_state,
            'destination_city' => $this->destination_city,
            'departure_date' => $this->departure_date->toDateString(),
            'return_date' => $this->return_date?->toDateString(),
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'status_histories' => TravelOrderStatusHistoryResource::collection(
                $this->whenLoaded('statusHistories')
            ),
        ];
    }
}
