<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when LLM operations fail.
 *
 * This exception wraps errors from LLM providers to provide
 * consistent error handling across the application.
 */
class LLMException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        protected ?array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get additional context about the error.
     */
    public function getContext(): array
    {
        return $this->context ?? [];
    }

    /**
     * Create exception for rate limit errors.
     */
    public static function rateLimitExceeded(string $provider, int $retryAfter = 60): self
    {
        return new self(
            "Rate limit exceeded for {$provider}. Retry after {$retryAfter} seconds.",
            429,
            null,
            ['provider' => $provider, 'retry_after' => $retryAfter]
        );
    }

    /**
     * Create exception for authentication errors.
     */
    public static function authenticationFailed(string $provider): self
    {
        return new self(
            "Authentication failed for {$provider}. Check your API key.",
            401,
            null,
            ['provider' => $provider]
        );
    }

    /**
     * Create exception for service unavailable.
     */
    public static function serviceUnavailable(string $provider): self
    {
        return new self(
            "LLM service {$provider} is currently unavailable.",
            503,
            null,
            ['provider' => $provider]
        );
    }
}
