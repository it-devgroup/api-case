<?php

namespace App\Models;

use Database\Factories\StripeWebhookEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['stripe_event_id', 'type', 'processed_at'])]
class StripeWebhookEvent extends Model
{
    /** @use HasFactory<StripeWebhookEventFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
