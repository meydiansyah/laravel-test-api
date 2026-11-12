<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Header X-Api-Token      : harus sama dengan API_TOKEN (.env)
     * Header X-Payload-Token  : base64url(json).base64url(hmac-sha256) dengan API_PAYLOAD_SECRET
     */
    public function __invoke(Request $request): JsonResponse
    {
        $apiToken = (string) config('services.api.token');
        $secret = (string) config('services.api.payload_secret');

        if ($apiToken === '' || $secret === '' || ! hash_equals($apiToken, (string) $request->header('X-Api-Token'))) {
            return response()->json(['message' => 'Invalid API token'], 401);
        }

        $payload = $this->decodePayload((string) $request->header('X-Payload-Token'), $secret);

        if ($payload === null) {
            return response()->json(['message' => 'Invalid payload token'], 401);
        }

        if (($payload['exp'] ?? 0) < time()) {
            return response()->json(['message' => 'Payload token expired'], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'user_id' => $payload['user_id'] ?? null,
                'name' => $payload['name'] ?? null,
                'role' => $payload['role'] ?? null,
                'issued_at' => isset($payload['iat']) ? date('c', $payload['iat']) : null,
                'expires_at' => date('c', $payload['exp']),
                'verified_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function decodePayload(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            return null;
        }

        [$body, $sig] = $parts;
        $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', $body, $secret, true)), '+/', '-_'), '=');

        if (! hash_equals($expected, $sig)) {
            return null;
        }

        $data = json_decode(base64_decode(strtr($body, '-_', '+/')), true);

        return is_array($data) ? $data : null;
    }
}
