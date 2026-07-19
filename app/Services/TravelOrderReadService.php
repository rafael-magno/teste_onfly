<?php

namespace App\Services;

use App\Models\TravelOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TravelOrderReadService
{
    public function find(int $id): TravelOrder
    {
        return TravelOrder::query()
            ->visibleTo(Auth::user())
            ->with('statusHistories.user')
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $query = TravelOrder::query()->visibleTo(Auth::user());

        return $this->applyFilters($query, $filters)
            ->paginate($filters['per_page'] ?? 15, page: $filters['page'] ?? 1);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query->where(function (Builder $query) use ($filters) {
            $likeFilters = ['destination_country', 'destination_state', 'destination_city'];

            foreach ($likeFilters as $field) {
                if ($filters[$field] ?? null) {
                    $query->whereLike($field, '%'.$filters[$field].'%');
                }
            }

            if ($filters['status'] ?? null) {
                $query->where('status', $filters['status']);
            }

            if ($filters['departure_from'] ?? null) {
                $query->whereDate('departure_date', '>=', $filters['departure_from']);
            }

            if ($filters['departure_to'] ?? null) {
                $query->whereDate('departure_date', '<=', $filters['departure_to']);
            }
        });
    }
}
