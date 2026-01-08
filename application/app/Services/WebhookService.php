<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Webhook dispatcher service.
 *
 * Handles webhook delivery with retries, signature generation,
 * and delivery tracking.
 */
class WebhookService
{
    /**
     * Dispatch webhook for an event
     */
    public function dispatch(string $event, array $payload): void
    {
        $webhooks = Webhook::where('active', true)->get();

        foreach ($webhooks as $webhook) {
            if ($webhook->isSubscribedTo($event)) {
                $this->deliverWebhook($webhook, $event, $payload);
            }
        }
    }

    /**
     * Deliver webhook to a single endpoint
     */
    protected function deliverWebhook(Webhook $webhook, string $event, array $payload): void
    {
        $fullPayload = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => $payload,
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => $event,
            'X-Webhook-ID' => $webhook->id,
        ];

        // Add signature if secret is configured
        if ($webhook->secret) {
            $signature = $this->generateSignature($fullPayload, $webhook->secret);
            $headers['X-Webhook-Signature'] = $signature;
        }

        // Try to deliver with retries
        $maxAttempts = $webhook->retry_count;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders($headers)
                    ->post($webhook->url, $fullPayload);

                $success = $response->successful();

                // Log delivery
                WebhookDelivery::create([
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                    'payload' => $fullPayload,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'attempt' => $attempt,
                    'success' => $success,
                ]);

                if ($success) {
                    $webhook->markAsTriggered();
                    break; // Success, no need to retry
                }

                // Wait before retry (exponential backoff)
                if ($attempt < $maxAttempts) {
                    sleep(pow(2, $attempt)); // 2, 4, 8 seconds
                }

            } catch (\Exception $e) {
                // Log failed delivery
                WebhookDelivery::create([
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                    'payload' => $fullPayload,
                    'attempt' => $attempt,
                    'success' => false,
                    'error_message' => $e->getMessage(),
                ]);

                Log::error('Webhook delivery failed', [
                    'webhook_id' => $webhook->id,
                    'event' => $event,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                // Wait before retry
                if ($attempt < $maxAttempts) {
                    sleep(pow(2, $attempt));
                }
            }
        }
    }

    /**
     * Generate HMAC signature for webhook payload
     */
    protected function generateSignature(array $payload, string $secret): string
    {
        $jsonPayload = json_encode($payload);

        return 'sha256='.hash_hmac('sha256', $jsonPayload, $secret);
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = 'sha256='.hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}
