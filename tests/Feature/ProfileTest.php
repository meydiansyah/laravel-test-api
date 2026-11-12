<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProfileTest extends TestCase
{
    private function token(array $payload, ?string $secret = null): string
    {
        $b64 = fn ($s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $body = $b64(json_encode($payload));

        return $body.'.'.$b64(hash_hmac('sha256', $body, $secret ?? config('services.api.payload_secret'), true));
    }

    private function call_(?string $api, ?string $payload)
    {
        return $this->withHeaders(array_filter([
            'X-Api-Token' => $api,
            'X-Payload-Token' => $payload,
        ]))->getJson('/api/profile');
    }

    public function test_valid_headers_return_profile(): void
    {
        $p = $this->token(['user_id' => 1, 'name' => 'Budi', 'role' => 'admin', 'iat' => time(), 'exp' => time() + 60]);

        $this->call_(config('services.api.token'), $p)
            ->assertOk()
            ->assertJsonPath('data.name', 'Budi')
            ->assertJsonPath('data.role', 'admin');
    }

    public function test_rejects_bad_api_token_bad_signature_and_expired(): void
    {
        $ok = ['user_id' => 1, 'exp' => time() + 60];
        $api = config('services.api.token');

        $this->call_('salah', $this->token($ok))->assertUnauthorized();
        $this->call_(null, $this->token($ok))->assertUnauthorized();
        $this->call_($api, $this->token($ok, 'secret-lain'))->assertUnauthorized();
        $this->call_($api, $this->token(['exp' => time() - 1]))->assertUnauthorized();
        $this->call_($api, null)->assertUnauthorized();
    }
}
