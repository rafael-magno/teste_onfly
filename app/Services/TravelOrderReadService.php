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
            $this->applyLikeFilters($query, $filters);
            $this->applyStatusFilter($query, $filters);
            $this->applyDateRangeFilter($query, $filters, 'departure_date', 'departure_from', 'departure_to');
            $this->applyDateRangeFilter($query, $filters, 'return_date', 'return_from', 'return_to');
            $this->applyOneWayFilter($query, $filters);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyLikeFilters(Builder &$query, array $filters): void
    {
        foreach (['destination_country', 'destination_state', 'destination_city'] as $field) {
            if (!empty($filters[$field])) {
                $query->whereLike($field, '%'.$filters[$field].'%');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyStatusFilter(Builder &$query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyDateRangeFilter(Builder &$query, array $filters, string $column, string $fromKey, string $toKey): void
    {
        if (!empty($filters[$fromKey])) {
            $query->whereDate($column, '>=', $filters[$fromKey]);
        }

        if (!empty($filters[$toKey])) {
            $query->whereDate($column, '<=', $filters[$toKey]);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyOneWayFilter(Builder &$query, array $filters): void
    {
        if (! isset($filters['one_way']) || $filters['one_way'] === null) {
            return;
        }

        filter_var($filters['one_way'], FILTER_VALIDATE_BOOLEAN)
            ? $query->whereNull('return_date')
            : $query->whereNotNull('return_date');
    }
}
