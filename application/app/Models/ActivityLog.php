<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Activity log model for audit trail.
 *
 * Records all important actions in the system for
 * monitoring, debugging, and compliance.
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'event',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Get the subject of the activity
     */
    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Get the causer of the activity
     */
    public function causer()
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by log name
     */
    public function scopeInLog($query, string $logName)
    {
        return $query->where('log_name', $logName);
    }

    /**
     * Scope to filter by event
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope to filter by causer
     */
    public function scopeCausedBy($query, Model $causer)
    {
        return $query->where('causer_type', get_class($causer))
            ->where('causer_id', $causer->id);
    }

    /**
     * Scope to filter by subject
     */
    public function scopeForSubject($query, Model $subject)
    {
        return $query->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id);
    }
}
