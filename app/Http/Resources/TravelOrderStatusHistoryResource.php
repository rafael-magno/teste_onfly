<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelOrderStatusHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status->value,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'reason' => $this->reason,
            'created_at' => $this->created_at,
        ];
    }
}
