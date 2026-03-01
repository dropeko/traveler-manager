<?php

namespace App\Http\Requests\Api\V1;

use App\Models\TravelOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TravelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requester_name' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'departure_date' => ['required', 'date'],
            'return_date' => ['required', 'date', 'after_or_equal:departure_date'],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    TravelOrder::STATUS_REQUESTED,
                    TravelOrder::STATUS_APPROVED,
                    TravelOrder::STATUS_CANCELLED,
                ]),
            ],
        ];
    }
}