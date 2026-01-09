<?php

namespace App\Contracts;

/**
 * Contract for LLM (Large Language Model) clients.
 *
 * This interface defines the standard contract for interacting with
 * different LLM providers (OpenAI, Anthropic, Groq, Ollama, etc).
 *
 * Why this exists:
 * - Provides abstraction layer for different LLM providers
 * - Enables easy switching between providers without changing business logic
 * - Standardizes request/response format across providers
 * - Makes testing easier with mock implementations
 *
 * Callers should rely on:
 * - The chat() method returning a consistent response format
 * - Errors being handled and wrapped in exceptions
 * - Rate limiting and retries being handled internally
 */
interface LLMClient
{
    /**
     * Send a chat completion request to the LLM.
     *
     * @param  array  $messages  Array of message objects with 'role' and 'content'
     * @param  array  $options  Additional options (model, temperature, max_tokens, etc)
     * @return array Response with 'content', 'model', 'usage', etc
     *
     * @throws \App\Exceptions\LLMException
     */
    public function chat(array $messages, array $options = []): array;

    /**
     * Get the name of the LLM provider.
     */
    public function getProvider(): string;

    /**
     * Check if the LLM service is available.
     */
    public function isAvailable(): bool;
}
