<?php

namespace App\Http\Controllers\Api;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Support\JsonApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate();

        return OrderResource::collection($orders)->response();
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $lines = collect($request->validated('items'));
        $requestedQuantities = $lines->groupBy('productId')
            ->map(fn ($group) => $group->sum('quantity'));

        try {
            $order = DB::transaction(function () use ($request, $lines, $requestedQuantities) {
                $products = Product::query()
                    ->whereIn('id', $requestedQuantities->keys())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $insufficient = $requestedQuantities
                    ->filter(fn ($quantity, $productId) => $products[$productId]->stock_quantity < $quantity)
                    ->map(fn ($quantity, $productId) => $products[$productId]->sku)
                    ->values()
                    ->all();

                if ($insufficient !== []) {
                    throw new InsufficientStockException($insufficient);
                }

                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'status' => OrderStatus::Pending,
                    'currency' => 'nok',
                    'subtotal' => 0,
                    'total' => 0,
                    'expires_at' => now()->addMinutes(30),
                ]);

                $subtotal = 0;

                foreach ($lines as $line) {
                    $product = $products[$line['productId']];
                    $unitPrice = (int) round($product->price * 100);
                    $lineTotal = $unitPrice * $line['quantity'];
                    $subtotal += $lineTotal;

                    $order->items()->create([
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'title' => $product->title,
                        'unit_price' => $unitPrice,
                        'quantity' => $line['quantity'],
                        'line_total' => $lineTotal,
                    ]);
                }

                foreach ($requestedQuantities as $productId => $quantity) {
                    $products[$productId]->decrement('stock_quantity', $quantity);
                }

                $order->update(['subtotal' => $subtotal, 'total' => $subtotal]);

                return $order;
            });
        } catch (InsufficientStockException $e) {
            return JsonApi::error('422', 'Unprocessable Entity', 'Insufficient stock for: '.implode(', ', $e->skus), null, 'insufficient_stock');
        }

        return (new OrderResource($order->load('items')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        return (new OrderResource($order->load('items', 'payments')))->response();
    }

    public function checkout(Request $request, Order $order, PaymentGateway $gateway): JsonResponse
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        if ($order->status !== OrderStatus::Pending || $order->expires_at?->isPast()) {
            return JsonApi::error('409', 'Conflict', 'Order is not open for checkout.');
        }

        $session = $gateway->createCheckoutSession($order->load('items'));

        $order->update(['stripe_checkout_session_id' => $session->id]);

        return response()->json(['data' => ['checkoutUrl' => $session->url]]);
    }
}
