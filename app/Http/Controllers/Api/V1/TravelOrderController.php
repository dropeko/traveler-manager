<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TravelOrderRequest;
use App\Http\Requests\Api\V1\TravelOrderIndexRequest;
use App\Http\Requests\Api\V1\UpdateTravelOrderStatusRequest;
use App\Http\Resources\TravelOrderResource;
use App\Http\Resources\TravelOrderDetailsResource;
use App\Models\TravelOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TravelOrderController extends Controller
{
    public function createTravelOrder(TravelOrderRequest $request): JsonResponse
    {
        $user = $request->user('api');

        $data = $request->validated();

        if (! $user->isAdmin()) {
            $data['status'] = TravelOrder::STATUS_REQUESTED;
        } else {
            $data['status'] = $data['status'] ?? TravelOrder::STATUS_REQUESTED;
        }

        $order = $user->travelOrders()->create($data);

        return response()->json([
            'data' => new TravelOrderResource($order->refresh()),
        ], 201);
    }

    public function showByOrderCode(Request $request, string $order_code): JsonResponse
    {
        $user = $request->user('api');

        $query = $user->isAdmin()
            ? TravelOrder::query()
            : $user->travelOrders();

        $order = $query->where('order_code', $order_code)->firstOrFail();

        return response()->json([
            'data' => new TravelOrderDetailsResource($order),
        ]);
    }

    public function listTravelOrders(TravelOrderIndexRequest $request)
    {
        $user = $request->user('api');

        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 15);

        $query = TravelOrder::query();

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['destination'])) {
            $query->where('destination', 'like', '%' . $filters['destination'] . '%');
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }
        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        if (! empty($filters['travel_from'])) {
            $query->whereDate('return_date', '>=', $filters['travel_from']);
        }
        if (! empty($filters['travel_to'])) {
            $query->whereDate('departure_date', '<=', $filters['travel_to']);
        }

        $paginator = $query
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return TravelOrderResource::collection($paginator->getCollection());
    }

    public function updateTravelOrderStatus(UpdateTravelOrderStatusRequest $request, string $order_code): JsonResponse
    {
        $user = $request->user('api');

        if (! $user->isAdmin()) {
            return response()->json([
                'message' => 'Apenas administradores podem alterar o status dos pedidos de viagem.',
            ], 403);
        }

        $order = TravelOrder::query()
            ->where('order_code', $order_code)
            ->firstOrFail();

        $order->update([
            'status' => $request->validated('status'),
        ]);

        return response()->json([
            'data' => new TravelOrderResource($order->refresh()),
        ]);
    }
}