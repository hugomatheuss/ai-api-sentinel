<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para autenticação via API Token.
 *
 * Verifica o header Authorization: Bearer {token}
 * e valida o token contra o banco de dados.
 */
class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'API token is required. Please provide a valid token in the Authorization header.',
            ], 401);
        }

        $apiToken = ApiToken::findByPlainToken($token);

        if (! $apiToken) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Invalid or expired API token.',
            ], 401);
        }

        // Check scope if specified
        if ($scope && ! $apiToken->can($scope)) {
            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
                'message' => "This token does not have access to scope: {$scope}",
            ], 403);
        }

        // Mark token as used
        $apiToken->markAsUsed();

        // Attach token to request for later use
        $request->attributes->set('api_token', $apiToken);

        // Attach user if token has one
        if ($apiToken->user) {
            $request->setUserResolver(fn () => $apiToken->user);
        }

        return $next($request);
    }

    /**
     * Extract token from request
     */
    protected function extractToken(Request $request): ?string
    {
        // Try Authorization header first (Bearer token)
        $header = $request->header('Authorization');
        if ($header && preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return $matches[1];
        }

        // Fallback to X-API-Token header
        if ($request->header('X-API-Token')) {
            return $request->header('X-API-Token');
        }

        // Fallback to query parameter (not recommended for production)
        return $request->query('api_token');
    }
}
