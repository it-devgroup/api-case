<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'stripe',
            'stripe_checkout_session_id' => 'cs_test_'.fake()->unique()->bothify('##########'),
            'stripe_payment_intent_id' => 'pi_'.fake()->unique()->bothify('##########'),
            'status' => PaymentStatus::Pending,
            'amount' => fake()->numberBetween(1000, 50000),
            'currency' => 'nok',
            'raw_payload' => null,
        ];
    }

    /**
     * Indicate that the payment succeeded.
     */
    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Succeeded,
        ]);
    }
}
