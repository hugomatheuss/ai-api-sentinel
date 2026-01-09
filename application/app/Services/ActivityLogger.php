<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Activity logger service.
 *
 * Provides easy logging of activities for audit trail,
 * monitoring, and debugging.
 */
class ActivityLogger
{
    /**
     * Log an activity
     */
    public function log(string $logName, string $description): self
    {
        $this->activity = ActivityLog::create([
            'log_name' => $logName,
            'description' => $description,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return $this;
    }

    /**
     * Set the subject of the activity
     */
    public function on(?Model $subject): self
    {
        if ($subject && $this->activity) {
            $this->activity->update([
                'subject_type' => get_class($subject),
                'subject_id' => $subject->id,
            ]);
        }

        return $this;
    }

    /**
     * Set who caused the activity
     */
    public function by(?Model $causer): self
    {
        if ($causer && $this->activity) {
            $this->activity->update([
                'causer_type' => get_class($causer),
                'causer_id' => $causer->id,
            ]);
        }

        return $this;
    }

    /**
     * Set the event type
     */
    public function event(string $event): self
    {
        if ($this->activity) {
            $this->activity->update(['event' => $event]);
        }

        return $this;
    }

    /**
     * Add properties/metadata
     */
    public function withProperties(array $properties): self
    {
        if ($this->activity) {
            $this->activity->update(['properties' => $properties]);
        }

        return $this;
    }

    /**
     * Quick log method for common patterns
     */
    public static function activity(string $logName): self
    {
        return new self;
    }

    protected ?ActivityLog $activity = null;
}
