<?php

namespace Database\Factories;

use App\Models\StripeWebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StripeWebhookEvent>
 */
class StripeWebhookEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stripe_event_id' => 'evt_'.fake()->unique()->bothify('##########'),
            'type' => 'checkout.session.completed',
            'processed_at' => now(),
        ];
    }
}
