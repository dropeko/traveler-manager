<?php

namespace App\Http\Requests\Api\V1;

use App\Models\TravelOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TravelOrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in([
                TravelOrder::STATUS_REQUESTED,
                TravelOrder::STATUS_APPROVED,
                TravelOrder::STATUS_CANCELLED,
            ])],

            'destination' => ['sometimes', 'string', 'max:255'],

            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date', 'after_or_equal:created_from'],

            'travel_from' => ['sometimes', 'date'],
            'travel_to' => ['sometimes', 'date', 'after_or_equal:travel_from'],

            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}