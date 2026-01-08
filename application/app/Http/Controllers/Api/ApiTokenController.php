<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * API Token Management Controller.
 *
 * Allows users to create, list, and revoke API tokens.
 */
class ApiTokenController extends Controller
{
    /**
     * Create a new API token.
     *
     * POST /api/v1/tokens
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'scopes' => 'sometimes|array',
            'scopes.*' => 'string',
            'expires_in_days' => 'sometimes|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = ApiToken::generate(
            name: $request->name,
            userId: $request->user()?->id,
            scopes: $request->scopes,
            expiresInDays: $request->expires_in_days
        );

        return response()->json([
            'success' => true,
            'message' => 'API token created successfully',
            'token' => $result['plain_token'],
            'token_id' => $result['token']->id,
            'expires_at' => $result['token']->expires_at?->toIso8601String(),
            'warning' => 'Store this token securely. It will not be shown again.',
        ], 201);
    }

    /**
     * List all API tokens for the authenticated user.
     *
     * GET /api/v1/tokens
     */
    public function index(Request $request)
    {
        $tokens = ApiToken::when($request->user(), function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'scopes' => $token->scopes,
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'expires_at' => $token->expires_at?->toIso8601String(),
                    'created_at' => $token->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'tokens' => $tokens,
        ]);
    }

    /**
     * Revoke an API token.
     *
     * DELETE /api/v1/tokens/{id}
     */
    public function destroy(Request $request, int $id)
    {
        $token = ApiToken::find($id);

        if (! $token) {
            return response()->json([
                'success' => false,
                'error' => 'Token not found',
            ], 404);
        }

        // Check ownership if user is authenticated
        if ($request->user() && $token->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'You do not have permission to revoke this token.',
            ], 403);
        }

        $token->revoke();

        return response()->json([
            'success' => true,
            'message' => 'API token revoked successfully',
        ]);
    }
}
