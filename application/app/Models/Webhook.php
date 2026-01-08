<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Webhook model for event notifications.
 *
 * Allows users to register HTTP endpoints that will be called
 * when specific events occur (contract validation, breaking changes, etc).
 */
class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'url',
        'secret',
        'events',
        'active',
        'retry_count',
        'last_triggered_at',
    ];

    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Check if webhook is subscribed to an event
     */
    public function isSubscribedTo(string $event): bool
    {
        if (! $this->active) {
            return false;
        }

        // Wildcard subscription
        if (in_array('*', $this->events)) {
            return true;
        }

        return in_array($event, $this->events);
    }

    /**
     * Get webhook deliveries
     */
    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Get recent deliveries
     */
    public function recentDeliveries(int $limit = 10)
    {
        return $this->deliveries()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get delivery success rate
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->deliveries()->count();

        if ($total === 0) {
            return 0;
        }

        $successful = $this->deliveries()->where('success', true)->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Mark as triggered
     */
    public function markAsTriggered(): void
    {
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * User that owns the webhook
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
