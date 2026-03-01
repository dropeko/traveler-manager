<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TravelOrderRequest;
use App\Http\Resources\TravelOrderResource;
use App\Models\TravelOrder;
use Illuminate\Http\JsonResponse;

class TravelOrderController extends Controller
{
    public function store(TravelOrderRequest $request): JsonResponse
    {
        $user = $request->user('api');

        $data = $request->validated();
        $data['status'] = $data['status'] ?? TravelOrder::STATUS_REQUESTED;

        $order = $user->travelOrders()->create($data);

        return response()->json([
            'data' => new TravelOrderResource($order->refresh()),
        ], 201);
    }
}