<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Export orders as an Excel spreadsheet. Accepts the same `status` and
     * `userId` filters as the index endpoint.
     */
    public function export(Request $request): StreamedResponse
    {
        $rows = Order::query()
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('userId'), fn ($query, $userId) => $query->where('user_id', $userId))
            ->latest()
            ->get()
            ->map(fn (Order $order) => $this->toExportRow($order));

        $response = (new FastExcel($rows))->download('orders.xlsx');
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        return $response;
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

    /**
     * @return array<string, mixed>
     */
    private function toExportRow(Order $order): array
    {
        return [
            'id' => $order->id,
            'userId' => $order->user_id,
            'status' => $order->status->value,
            'currency' => $order->currency,
            'subtotal' => $order->subtotal,
            'total' => $order->total,
            'stripeCheckoutSessionId' => $order->stripe_checkout_session_id,
            'stripePaymentIntentId' => $order->stripe_payment_intent_id,
            'paidAt' => $order->paid_at?->toJSON(),
            'cancelledAt' => $order->cancelled_at?->toJSON(),
            'expiresAt' => $order->expires_at?->toJSON(),
            'createdAt' => $order->created_at?->toJSON(),
            'updatedAt' => $order->updated_at?->toJSON(),
        ];
    }
}
