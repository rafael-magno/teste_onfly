<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Query parameters
 */
class IndexTravelOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'in:requested,approved,cancelled'],
            'destination_country' => ['sometimes', 'string'],
            'destination_state' => ['sometimes', 'string'],
            'destination_city' => ['sometimes', 'string'],
            'departure_from' => ['sometimes', 'date'],
            'departure_to' => [
                'sometimes',
                'date',
                Rule::when($this->filled('departure_from'), ['after_or_equal:departure_from']),
            ],
            'return_from' => [
                'sometimes',
                'date',
                'prohibited_if:one_way,1',
                Rule::when($this->filled('return_to'), ['before_or_equal:return_to']),
            ],
            'return_to' => [
                'sometimes',
                'date',
                'prohibited_if:one_way,1',
                Rule::when($this->filled('return_from'), ['after_or_equal:return_from']),
            ],
            'one_way' => ['sometimes', 'in:0,1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
