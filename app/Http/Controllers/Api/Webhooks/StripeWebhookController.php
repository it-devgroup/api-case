<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StripeWebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                (string) config('services.stripe.webhook_secret'),
            );
        } catch (SignatureVerificationException|UnexpectedValueException) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        if (! $this->recordEventOnce($event)) {
            return response()->json(['message' => 'Already processed.']);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event),
            'checkout.session.expired' => $this->handleCheckoutExpired($event),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event),
            'charge.refunded' => $this->handleChargeRefunded($event),
            default => null,
        };

        return response()->json(['message' => 'ok']);
    }

    /**
     * Insert the event's id as an idempotency guard. Returns false if it was
     * already recorded (unique constraint conflict), meaning this delivery
     * is a Stripe retry of an already-handled event.
     */
    private function recordEventOnce(Event $event): bool
    {
        try {
            StripeWebhookEvent::create([
                'stripe_event_id' => $event->id,
                'type' => $event->type,
                'processed_at' => now(),
            ]);
        } catch (QueryException $e) {
            if (str_contains(strtolower($e->getMessage()), 'unique constraint')) {
                return false;
            }

            throw $e;
        }

        return true;
    }

    private function handleCheckoutCompleted(Event $event): void
    {
        $session = $event->data->object;
        $order = $this->findOrderForSession($session);

        if (! $order || $order->status === OrderStatus::Paid) {
            return;
        }

        DB::transaction(function () use ($order, $session) {
            Payment::create([
                'order_id' => $order->id,
                'provider' => 'stripe',
                'stripe_checkout_session_id' => $session->id,
                'stripe_payment_intent_id' => $session->payment_intent ?? null,
                'status' => PaymentStatus::Succeeded,
                'amount' => $session->amount_total ?? $order->total,
                'currency' => $session->currency ?? $order->currency,
                'raw_payload' => $session->toArray(),
            ]);

            $order->update([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'stripe_payment_intent_id' => $session->payment_intent ?? $order->stripe_payment_intent_id,
            ]);
        });
    }

    private function handleCheckoutExpired(Event $event): void
    {
        $session = $event->data->object;
        $order = $this->findOrderForSession($session);

        if (! $order || $order->status !== OrderStatus::Pending) {
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                if ($item->product_id !== null) {
                    Product::whereKey($item->product_id)->increment('stock_quantity', $item->quantity);
                }
            }

            $order->update(['status' => OrderStatus::Expired]);
        });
    }

    private function handlePaymentFailed(Event $event): void
    {
        $paymentIntent = $event->data->object;
        $orderId = $paymentIntent->metadata->order_id ?? null;

        $order = $orderId
            ? Order::find($orderId)
            : Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $order) {
            return;
        }

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'stripe_payment_intent_id' => $paymentIntent->id,
            'status' => PaymentStatus::Failed,
            'amount' => $paymentIntent->amount ?? $order->total,
            'currency' => $paymentIntent->currency ?? $order->currency,
            'raw_payload' => $paymentIntent->toArray(),
        ]);
    }

    private function handleChargeRefunded(Event $event): void
    {
        $charge = $event->data->object;

        $payment = Payment::where('stripe_payment_intent_id', $charge->payment_intent)
            ->latest()
            ->first();

        if (! $payment) {
            return;
        }

        DB::transaction(function () use ($payment) {
            $payment->update(['status' => PaymentStatus::Refunded]);
            $payment->order->update(['status' => OrderStatus::Refunded]);
        });
    }

    private function findOrderForSession(mixed $session): ?Order
    {
        $orderId = $session->client_reference_id ?? $session->metadata->order_id ?? null;

        return $orderId ? Order::with('items')->find($orderId) : null;
    }
}
