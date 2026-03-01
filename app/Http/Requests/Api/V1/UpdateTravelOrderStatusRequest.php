<?php

namespace App\Http\Requests\Api\V1;

use App\Models\TravelOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTravelOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // a regra de admin será aplicada no controller
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    TravelOrder::STATUS_APPROVED,
                    TravelOrder::STATUS_CANCELLED,
                ]),
            ],
        ];
    }
}