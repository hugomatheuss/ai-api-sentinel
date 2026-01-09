<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Cache service for performance optimization.
 *
 * Provides centralized caching for expensive operations
 * like contract validation and parsing.
 */
class CacheService
{
    /**
     * Cache TTL in seconds
     */
    protected const DEFAULT_TTL = 3600; // 1 hour
    protected const VALIDATION_TTL = 1800; // 30 minutes
    protected const CONTRACT_TTL = 7200; // 2 hours

    /**
     * Cache validation results
     */
    public function cacheValidationResult(int $versionId, array $result): void
    {
        $key = $this->validationKey($versionId);
        Cache::put($key, $result, self::VALIDATION_TTL);
    }

    /**
     * Get cached validation result
     */
    public function getCachedValidation(int $versionId): ?array
    {
        $key = $this->validationKey($versionId);
        return Cache::get($key);
    }

    /**
     * Cache parsed contract
     */
    public function cacheParsedContract(int $versionId, array $parsed): void
    {
        $key = $this->parsedContractKey($versionId);
        Cache::put($key, $parsed, self::CONTRACT_TTL);
    }

    /**
     * Get cached parsed contract
     */
    public function getCachedParsedContract(int $versionId): ?array
    {
        $key = $this->parsedContractKey($versionId);
        return Cache::get($key);
    }

    /**
     * Cache breaking changes comparison
     */
    public function cacheBreakingChanges(int $oldVersionId, int $newVersionId, array $changes): void
    {
        $key = $this->breakingChangesKey($oldVersionId, $newVersionId);
        Cache::put($key, $changes, self::VALIDATION_TTL);
    }

    /**
     * Get cached breaking changes
     */
    public function getCachedBreakingChanges(int $oldVersionId, int $newVersionId): ?array
    {
        $key = $this->breakingChangesKey($oldVersionId, $newVersionId);
        return Cache::get($key);
    }

    /**
     * Invalidate all caches for a contract version
     */
    public function invalidateVersion(int $versionId): void
    {
        Cache::forget($this->validationKey($versionId));
        Cache::forget($this->parsedContractKey($versionId));

        // Also invalidate any comparisons involving this version
        Cache::flush(); // Simple approach - in production use tags
    }

    /**
     * Cache dashboard statistics
     */
    public function cacheDashboardStats(array $stats): void
    {
        Cache::put('dashboard:stats', $stats, 600); // 10 minutes
    }

    /**
     * Get cached dashboard statistics
     */
    public function getCachedDashboardStats(): ?array
    {
        return Cache::get('dashboard:stats');
    }

    /**
     * Remember (get or cache) a value
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Clear all application caches
     */
    public function clearAll(): void
    {
        Cache::flush();
    }

    /**
     * Generate validation cache key
     */
    protected function validationKey(int $versionId): string
    {
        return "validation:{$versionId}";
    }

    /**
     * Generate parsed contract cache key
     */
    protected function parsedContractKey(int $versionId): string
    {
        return "parsed_contract:{$versionId}";
    }

    /**
     * Generate breaking changes cache key
     */
    protected function breakingChangesKey(int $oldVersionId, int $newVersionId): string
    {
        return "breaking_changes:{$oldVersionId}:{$newVersionId}";
    }
}

