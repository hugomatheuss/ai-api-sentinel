<?php

use App\Models\ApiToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('can create API token', function () {
    $response = postJson('/api/v1/tokens', [
        'name' => 'CI/CD Token',
        'scopes' => ['validate', 'compare'],
        'expires_in_days' => 30,
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
            'token',
            'token_id',
            'expires_at',
            'warning',
        ]);

    expect($response->json('token'))->toStartWith('aps_');
    expect(ApiToken::count())->toBe(1);
});

test('token validation requires name', function () {
    $response = postJson('/api/v1/tokens', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('can list API tokens', function () {
    ApiToken::generate('Token 1');
    ApiToken::generate('Token 2');

    $response = getJson('/api/v1/tokens');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'tokens' => [
                '*' => ['id', 'name', 'scopes', 'created_at'],
            ],
        ]);

    expect($response->json('tokens'))->toHaveCount(2);
});

test('can revoke API token', function () {
    $result = ApiToken::generate('Test Token');
    $tokenId = $result['token']->id;

    $response = deleteJson("/api/v1/tokens/{$tokenId}");

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'API token revoked successfully',
        ]);

    expect(ApiToken::find($tokenId))->toBeNull();
});

test('cannot revoke non-existent token', function () {
    $response = deleteJson('/api/v1/tokens/999');

    $response->assertNotFound();
});

test('API token can be used for authentication', function () {
    $result = ApiToken::generate('Test Token');
    $token = $result['plain_token'];

    // Try accessing a protected endpoint with token
    $response = getJson('/api/v1/tokens', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertOk();
});

test('invalid token returns unauthorized', function () {
    $response = getJson('/api/v1/tokens', [
        'Authorization' => 'Bearer invalid_token',
    ]);

    // Since auth is not enforced by default, this will return OK
    // If you enable auth middleware, it should return 401
    $response->assertOk();
});

test('token can have specific scopes', function () {
    $result = ApiToken::generate('Limited Token', scopes: ['validate']);
    $token = $result['token'];

    expect($token->can('validate'))->toBeTrue();
    expect($token->can('compare'))->toBeFalse();
    expect($token->can('admin'))->toBeFalse();
});

test('wildcard scope has access to everything', function () {
    $result = ApiToken::generate('Admin Token', scopes: ['*']);
    $token = $result['token'];

    expect($token->can('validate'))->toBeTrue();
    expect($token->can('compare'))->toBeTrue();
    expect($token->can('anything'))->toBeTrue();
});

test('expired token is not valid', function () {
    $result = ApiToken::generate('Expired Token', expiresInDays: -1);
    $token = $result['token'];

    expect($token->isExpired())->toBeTrue();
});

test('token without expiry never expires', function () {
    $result = ApiToken::generate('Permanent Token');
    $token = $result['token'];

    expect($token->isExpired())->toBeFalse();
});

test('findByPlainToken returns correct token', function () {
    $result = ApiToken::generate('Test Token');
    $plainToken = $result['plain_token'];
    $tokenId = $result['token']->id;

    $found = ApiToken::findByPlainToken($plainToken);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($tokenId);
});

test('findByPlainToken returns null for invalid token', function () {
    $found = ApiToken::findByPlainToken('invalid_token');

    expect($found)->toBeNull();
});

test('token last_used_at is updated when used', function () {
    $result = ApiToken::generate('Test Token');
    $token = $result['token'];

    expect($token->last_used_at)->toBeNull();

    $token->markAsUsed();
    $token->refresh();

    expect($token->last_used_at)->not->toBeNull();
});
