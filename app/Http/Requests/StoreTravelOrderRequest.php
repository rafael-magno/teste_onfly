<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTravelOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'destination_country' => ['required', 'string', 'max:255'],
            'destination_state' => ['nullable', 'string', 'max:255'],
            'destination_city' => ['required', 'string', 'max:255'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['nullable', 'date', 'after_or_equal:departure_date'],
        ];
    }

    /**
     * Parameters for the request body, used for API documentation.
     */
    public function bodyParameters()
    {
        return [
            'destination_country' => [
                'example' => 'Brasil',
            ],
            'destination_state' => [
                'example' => 'MG',
            ],
            'destination_city' => [
                'example' => 'Belo Horizonte',
            ],
            'departure_date' => [
                'example' => now()->addWeek()->toDateString(),
            ],
            'return_date' => [
                'example' => now()->addWeeks(2)->toDateString(),
            ],
        ];
    }
}
