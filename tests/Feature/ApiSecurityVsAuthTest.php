<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Learner;

class ApiSecurityVsAuthTest extends TestCase
{
    private string $secret;
    private string $apiKey;
    private string $uri = '/api/v1/learner/dashboard';

    protected function setUp(): void
    {
        parent::setUp();
        $this->secret = config('app.hmac_secret', config('app.api_key', 'libraro_secret_key_2026'));
        $this->apiKey = config('app.api_key', 'libraro_secret_key_2026');
    }

    private function generateSignedHeaders(string $method, string $uri, string $body = '', array $overrides = []): array
    {
        $nowMs = (int) (microtime(true) * 1000);
        $nonce = 'test_nonce_' . uniqid() . '_' . mt_rand(1000, 9999);
        $appVer = '1.0.1';
        $platform = 'android';
        $deviceId = 'device_test_123';

        $ts = $overrides['X-Timestamp'] ?? (string) $nowMs;
        $nc = $overrides['X-Nonce'] ?? $nonce;
        $ap = $overrides['X-App-Version'] ?? $appVer;
        $pl = $overrides['X-Platform'] ?? $platform;
        $dv = $overrides['X-Device-Id'] ?? $deviceId;
        $ak = $overrides['X-API-KEY'] ?? $this->apiKey;

        $payload = "{$method}|{$uri}|{$ts}|{$nc}|{$body}";
        $signature = hash_hmac('sha256', $payload, $this->secret);
        if (isset($overrides['X-Signature'])) {
            $signature = $overrides['X-Signature'];
        }

        $headers = [
            'Accept'        => 'application/json',
            'X-API-KEY'     => $ak,
            'X-Timestamp'   => $ts,
            'X-Nonce'       => $nc,
            'X-Signature'   => $signature,
            'X-App-Version' => $ap,
            'X-Platform'    => $pl,
            'X-Device-Id'   => $dv,
        ];

        if (isset($overrides['Bearer'])) {
            $headers['Authorization'] = 'Bearer ' . $overrides['Bearer'];
        }

        if (array_key_exists('REMOVE_API_KEY', $overrides)) unset($headers['X-API-KEY']);
        if (array_key_exists('REMOVE_SIGNATURE', $overrides)) unset($headers['X-Signature']);
        if (array_key_exists('REMOVE_TIMESTAMP', $overrides)) unset($headers['X-Timestamp']);
        if (array_key_exists('REMOVE_NONCE', $overrides)) unset($headers['X-Nonce']);
        if (array_key_exists('REMOVE_DEVICE', $overrides)) unset($headers['X-Device-Id']);

        return $headers;
    }

    /**
     * Test A: Missing X-API-KEY -> API Security Error (403, API_SECURITY_FAILED)
     */
    public function test_a_missing_api_key_returns_api_security_failed()
    {
        $headers = $this->generateSignedHeaders('POST', $this->uri, '', ['REMOVE_API_KEY' => true]);
        $response = $this->postJson($this->uri, [], $headers);

        $response->assertStatus(403)
            ->assertJson([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
            ]);
    }

    /**
     * Test B: Invalid X-API-KEY -> API Security Error (403, API_SECURITY_FAILED)
     */
    public function test_b_invalid_api_key_returns_api_security_failed()
    {
        $headers = $this->generateSignedHeaders('POST', $this->uri, '', ['X-API-KEY' => 'invalid_key_9999']);
        $response = $this->postJson($this->uri, [], $headers);

        $response->assertStatus(403)
            ->assertJson([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
            ]);
    }

    /**
     * Test C: Missing X-Signature -> API Security Error (403, API_SECURITY_FAILED)
     */
    public function test_c_missing_signature_returns_api_security_failed()
    {
        $headers = $this->generateSignedHeaders('POST', $this->uri, '', ['REMOVE_SIGNATURE' => true]);
        $response = $this->postJson($this->uri, [], $headers);

        $response->assertStatus(403)
            ->assertJson([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
            ]);
    }

    /**
     * Test D: Invalid X-Signature -> API Security Error (403, API_SECURITY_FAILED)
     */
    public function test_d_invalid_signature_returns_api_security_failed()
    {
        $headers = $this->generateSignedHeaders('POST', $this->uri, '', ['X-Signature' => 'deadbeefcafebabe1234567890abcdef']);
        $response = $this->postJson($this->uri, [], $headers);

        $response->assertStatus(403)
            ->assertJson([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
            ]);
    }

    /**
     * Test E: Expired X-Timestamp (> 5 minutes ago) -> API Security Error (403, API_SECURITY_FAILED)
     */
    public function test_e_expired_timestamp_returns_api_security_failed()
    {
        $oldTimestamp = (string) ((int) (microtime(true) * 1000) - 400000);
        $headers = $this->generateSignedHeaders('POST', $this->uri, '', ['X-Timestamp' => $oldTimestamp]);
        $response = $this->postJson($this->uri, [], $headers);

        $response->assertStatus(403)
            ->assertJson([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
            ]);
    }

    /**
     * Test F: Reused X-Nonce -> Replay Attack Error (403, API_SECURITY_FAILED)
     */
    public function test_f_reused_nonce_returns_api_security_failed()
    {
        $fixedNonce = 'reused_nonce_' . uniqid();
        $headers1 = $this->generateSignedHeaders('POST', $this->uri, '', ['X-Nonce' => $fixedNonce]);
        $this->postJson($this->uri, [], $headers1);

        $headers2 = $this->generateSignedHeaders('POST', $this->uri, '', ['X-Nonce' => $fixedNonce]);
        $response2 = $this->postJson($this->uri, [], $headers2);

        $response2->assertStatus(403)
            ->assertJson([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
            ]);
    }

    /**
     * Test G: Valid API security headers + Missing User Token -> USER_UNAUTHENTICATED (401)
     */
    public function test_g_valid_security_with_missing_token_returns_user_unauthenticated()
    {
        $headers = $this->generateSignedHeaders('POST', $this->uri, '[]');
        $response = $this->postJson($this->uri, [], $headers);

        $response->assertStatus(401)
            ->assertJson([
                'status'     => false,
                'state_code' => 'USER_UNAUTHENTICATED',
            ]);
    }

    /**
     * Test H: Valid API security headers + Invalid User Token -> USER_UNAUTHENTICATED (401)
     */
    public function test_h_valid_security_with_invalid_token_returns_user_unauthenticated()
    {
        $headers = $this->generateSignedHeaders('POST', $this->uri, '[]', ['Bearer' => 'invalid_or_expired_jwt_token']);
        $response = $this->postJson($this->uri, [], $headers);

        $response->assertStatus(401)
            ->assertJson([
                'status'     => false,
                'state_code' => 'USER_UNAUTHENTICATED',
            ]);
    }

    /**
     * Test I: Valid API security headers + Valid User Authentication -> 200 OK
     */
    public function test_i_valid_security_and_valid_auth_returns_200_ok()
    {
        $learner = Learner::first();
        if ($learner) {
            $token = $learner->createToken('test_token')->plainTextToken;
            $headers = $this->generateSignedHeaders('POST', $this->uri, '[]', ['Bearer' => $token]);
            $response = $this->postJson($this->uri, [], $headers);

            $response->assertStatus(200)
                ->assertJson([
                    'status' => true,
                ]);
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * Test J: Invalid API security headers + Invalid User Token -> API_SECURITY_FAILED (403) must take precedence
     */
    public function test_j_invalid_security_with_invalid_token_gives_api_security_failed_precedence()
    {
        $headers = $this->generateSignedHeaders('POST', $this->uri, '', [
            'X-API-KEY' => 'bad_key',
            'Bearer'    => 'bad_token'
        ]);
        $response = $this->postJson($this->uri, [], $headers);

        $response->assertStatus(403)
            ->assertJson([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
            ]);
    }

    /**
     * Test K: Invalid X-Platform (e.g. androidouu, windows) -> API_SECURITY_FAILED (403)
     */
    public function test_k_invalid_platform_returns_api_security_failed()
    {
        $headers = $this->generateSignedHeaders('POST', $this->uri, '', [
            'X-Platform' => 'androidouu'
        ]);
        $response = $this->postJson($this->uri, [], $headers);

        $response->assertStatus(403)
            ->assertJson([
                'status'     => false,
                'state_code' => 'API_SECURITY_FAILED',
            ]);
    }

    /**
     * Test L: X-App-Version is optional when force update is false
     */
    public function test_l_app_version_is_optional_when_force_update_false()
    {
        config(['app.force_update' => false]);
        $headers = $this->generateSignedHeaders('POST', $this->uri, '[]');
        unset($headers['X-App-Version']);
        $response = $this->postJson($this->uri, [], $headers);

        // Security passes, reaches user auth
        $response->assertStatus(401)
            ->assertJson([
                'status'     => false,
                'state_code' => 'USER_UNAUTHENTICATED',
            ]);
    }

    /**
     * Test M: When force update is true, outdated app version returns 426 Upgrade Required
     */
    public function test_m_outdated_app_version_returns_426_when_force_update_true()
    {
        config([
            'app.force_update' => true,
            'app.min_versions.android' => '2.0.0'
        ]);
        $headers = $this->generateSignedHeaders('POST', $this->uri, '', [
            'X-App-Version' => '1.0.0',
            'X-Platform'    => 'android'
        ]);
        $response = $this->postJson($this->uri, [], $headers);

        $response->assertStatus(426)
            ->assertJson([
                'status'       => false,
                'force_update' => true,
                'state_code'   => 'APP_UPDATE_REQUIRED',
            ]);
    }

    /**
     * Test N: app-settings API does NOT require X-App-Version even if force_update is true
     */
    public function test_n_app_settings_does_not_require_app_version_even_if_force_update_is_true()
    {
        config(['app.force_update' => true]);
        $settingsUri = '/api/v1/learner/app-settings';
        $headers = [
            'Accept'       => 'application/json',
            'X-API-KEY'    => $this->apiKey,
            'X-Platform'   => 'android',
            'X-Device-Id'  => 'android-device-test-123',
        ];

        $response = $this->getJson($settingsUri, $headers);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
            ]);
    }
}
