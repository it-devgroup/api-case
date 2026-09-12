<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(1000, 50000);

        return [
            'user_id' => User::factory(),
            'status' => OrderStatus::Pending,
            'currency' => 'nok',
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'stripe_checkout_session_id' => null,
            'stripe_payment_intent_id' => null,
            'paid_at' => null,
            'cancelled_at' => null,
            'expires_at' => now()->addMinutes(30),
        ];
    }

    /**
     * Indicate that the order has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
