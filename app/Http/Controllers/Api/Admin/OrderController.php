<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('userId'), fn ($query, $userId) => $query->where('user_id', $userId))
            ->latest()
            ->paginate();

        return OrderResource::collection($orders)->response();
    }

    public function show(Order $order): JsonResponse
    {
        return (new OrderResource($order->load('items', 'payments')))->response();
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $status = OrderStatus::from($request->validated('status'));

        $order->update([
            'status' => $status,
            'cancelled_at' => $status === OrderStatus::Cancelled ? ($order->cancelled_at ?? now()) : $order->cancelled_at,
        ]);

        return (new OrderResource($order->load('items', 'payments')))->response();
    }
}
