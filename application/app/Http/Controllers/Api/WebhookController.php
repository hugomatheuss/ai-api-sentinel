<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Webhook management controller.
 *
 * Allows users to register, list, update, and delete webhooks.
 */
class WebhookController extends Controller
{
    /**
     * List all webhooks
     */
    public function index(Request $request)
    {
        $webhooks = Webhook::when($request->user(), function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($webhook) {
                return [
                    'id' => $webhook->id,
                    'name' => $webhook->name,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'active' => $webhook->active,
                    'success_rate' => $webhook->success_rate,
                    'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
                    'created_at' => $webhook->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'webhooks' => $webhooks,
        ]);
    }

    /**
     * Create a new webhook
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'secret' => 'sometimes|string|min:16',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:*,contract.validated,contract.failed,breaking_changes.detected,version.uploaded',
            'retry_count' => 'sometimes|integer|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $webhook = Webhook::create([
            'user_id' => $request->user()?->id,
            'name' => $request->name,
            'url' => $request->url,
            'secret' => $request->secret,
            'events' => $request->events,
            'retry_count' => $request->retry_count ?? 3,
            'active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Webhook created successfully',
            'webhook' => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'url' => $webhook->url,
                'events' => $webhook->events,
            ],
        ], 201);
    }

    /**
     * Get webhook details
     */
    public function show(int $id)
    {
        $webhook = Webhook::find($id);

        if (! $webhook) {
            return response()->json([
                'success' => false,
                'error' => 'Webhook not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'webhook' => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'active' => $webhook->active,
                'retry_count' => $webhook->retry_count,
                'success_rate' => $webhook->success_rate,
                'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
                'created_at' => $webhook->created_at->toIso8601String(),
                'recent_deliveries' => $webhook->recentDeliveries()->map(function ($delivery) {
                    return [
                        'event' => $delivery->event,
                        'success' => $delivery->success,
                        'status_code' => $delivery->status_code,
                        'attempt' => $delivery->attempt,
                        'created_at' => $delivery->created_at->toIso8601String(),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update webhook
     */
    public function update(Request $request, int $id)
    {
        $webhook = Webhook::find($id);

        if (! $webhook) {
            return response()->json([
                'success' => false,
                'error' => 'Webhook not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'string',
            'active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $webhook->update($request->only(['name', 'url', 'events', 'active']));

        return response()->json([
            'success' => true,
            'message' => 'Webhook updated successfully',
            'webhook' => [
                'id' => $webhook->id,
                'name' => $webhook->name,
                'url' => $webhook->url,
                'events' => $webhook->events,
                'active' => $webhook->active,
            ],
        ]);
    }

    /**
     * Delete webhook
     */
    public function destroy(int $id)
    {
        $webhook = Webhook::find($id);

        if (! $webhook) {
            return response()->json([
                'success' => false,
                'error' => 'Webhook not found',
            ], 404);
        }

        $webhook->delete();

        return response()->json([
            'success' => true,
            'message' => 'Webhook deleted successfully',
        ]);
    }

    /**
     * Test webhook (send test ping)
     */
    public function test(int $id)
    {
        $webhook = Webhook::find($id);

        if (! $webhook) {
            return response()->json([
                'success' => false,
                'error' => 'Webhook not found',
            ], 404);
        }

        app(\App\Services\WebhookService::class)->dispatch('webhook.test', [
            'message' => 'This is a test webhook delivery',
            'webhook_id' => $webhook->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Test webhook sent',
        ]);
    }
}
