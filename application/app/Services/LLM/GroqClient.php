<?php

namespace App\Services\LLM;

use App\Contracts\LLMClient;
use App\Exceptions\LLMException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Groq LLM client implementation.
 *
 * Groq provides extremely fast inference for open-source models
 * like Llama 3, Mixtral, and Gemma with a generous free tier.
 *
 * Why Groq:
 * - Free tier with good limits
 * - Fastest inference speed (tokens/second)
 * - OpenAI-compatible API
 * - Good quality models (Llama 3, Mixtral)
 *
 * API Documentation: https://console.groq.com/docs
 */
class GroqClient implements LLMClient
{
    protected string $apiKey;

    protected string $baseUrl = 'https://api.groq.com/openai/v1';

    protected string $defaultModel = 'llama-3.3-70b-versatile';

    protected int $maxRetries = 3;

    protected int $timeout = 30;

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key', '');

        if (empty($this->apiKey)) {
            Log::warning('Groq API key not configured');
        }
    }

    /**
     * Send a chat completion request to Groq.
     */
    public function chat(array $messages, array $options = []): array
    {
        if (! $this->isAvailable()) {
            throw LLMException::serviceUnavailable('Groq');
        }

        $payload = [
            'model' => $options['model'] ?? $this->defaultModel,
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 1024,
        ];

        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])
                    ->timeout($this->timeout)
                    ->post("{$this->baseUrl}/chat/completions", $payload);

                if ($response->successful()) {
                    $data = $response->json();

                    return [
                        'content' => $data['choices'][0]['message']['content'] ?? '',
                        'model' => $data['model'] ?? $payload['model'],
                        'usage' => [
                            'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                            'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                            'total_tokens' => $data['usage']['total_tokens'] ?? 0,
                        ],
                        'finish_reason' => $data['choices'][0]['finish_reason'] ?? 'stop',
                    ];
                }

                // Handle rate limiting
                if ($response->status() === 429) {
                    $retryAfter = (int) $response->header('Retry-After', 60);

                    if ($attempt < $this->maxRetries) {
                        sleep(min($retryAfter, 5)); // Max 5 seconds wait

                        continue;
                    }

                    throw LLMException::rateLimitExceeded('Groq', $retryAfter);
                }

                // Handle authentication errors
                if ($response->status() === 401) {
                    throw LLMException::authenticationFailed('Groq');
                }

                // Other errors
                throw new LLMException(
                    "Groq API error: {$response->body()}",
                    $response->status(),
                    null,
                    ['response' => $response->json()]
                );

            } catch (LLMException $e) {
                throw $e;
            } catch (\Exception $e) {
                Log::error('Groq API request failed', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt >= $this->maxRetries) {
                    throw new LLMException(
                        "Failed to connect to Groq after {$this->maxRetries} attempts: {$e->getMessage()}",
                        0,
                        $e
                    );
                }

                sleep(pow(2, $attempt)); // Exponential backoff
            }
        }

        throw new LLMException('Max retries exceeded for Groq API');
    }

    /**
     * Get the provider name.
     */
    public function getProvider(): string
    {
        return 'groq';
    }

    /**
     * Check if Groq is available (has API key).
     */
    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Get available models.
     */
    public function getAvailableModels(): array
    {
        return [
            'llama-3.3-70b-versatile', // Recommended - best balance (NEW)
            'llama-3.1-8b-instant', // Fastest
            'mixtral-8x7b-32768', // Large context
            'gemma2-9b-it', // Google's model
            'llama3-70b-8192', // Llama 3 (older version, stable)
            'llama3-8b-8192', // Llama 3 8B (fast)
        ];
    }
}
