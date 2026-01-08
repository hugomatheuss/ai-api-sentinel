<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * API Token model for authentication.
 *
 * Provides secure token-based authentication for API endpoints.
 * Tokens are hashed using SHA-256 for security.
 */
class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'token',
        'scopes',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * Generate a new API token
     */
    public static function generate(string $name, ?int $userId = null, ?array $scopes = null, ?int $expiresInDays = null): array
    {
        // Generate random token
        $plainToken = 'aps_'.Str::random(40);

        // Hash token for storage
        $hashedToken = hash('sha256', $plainToken);

        // Create token record
        $token = self::create([
            'user_id' => $userId,
            'name' => $name,
            'token' => $hashedToken,
            'scopes' => $scopes ?? ['*'],
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
        ]);

        return [
            'token' => $token,
            'plain_token' => $plainToken,
        ];
    }

    /**
     * Find token by plain text value
     */
    public static function findByPlainToken(string $plainToken): ?self
    {
        $hashedToken = hash('sha256', $plainToken);

        return self::where('token', $hashedToken)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Check if token has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if token has access to a specific scope
     */
    public function can(string $scope): bool
    {
        if (! $this->scopes) {
            return false;
        }

        if (in_array('*', $this->scopes)) {
            return true;
        }

        return in_array($scope, $this->scopes);
    }

    /**
     * Update last used timestamp
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Revoke the token
     */
    public function revoke(): void
    {
        $this->delete();
    }

    /**
     * Get the user that owns the token
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
