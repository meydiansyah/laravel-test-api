<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('api:token {name} {role} {--user_id=1} {--ttl=3600 : detik}', function (string $name, string $role) {
    $b64 = fn ($s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    $body = $b64(json_encode([
        'user_id' => (int) $this->option('user_id'),
        'name' => $name,
        'role' => $role,
        'iat' => time(),
        'exp' => time() + (int) $this->option('ttl'),
    ]));

    $this->line($body.'.'.$b64(hash_hmac('sha256', $body, config('services.api.payload_secret'), true)));
})->purpose('Buat X-Payload-Token');
