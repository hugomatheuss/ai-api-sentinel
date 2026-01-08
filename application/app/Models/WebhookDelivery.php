<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Webhook delivery log.
 *
 * Records each attempt to deliver a webhook payload,
 * including success/failure and response details.
 */
class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'status_code',
        'response_body',
        'attempt',
        'success',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'success' => 'boolean',
    ];

    /**
     * Get the webhook
     */
    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
}
