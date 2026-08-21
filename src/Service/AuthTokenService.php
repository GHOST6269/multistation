<?php

namespace App\Service;

use App\Entity\User;

final class AuthTokenService
{
    public function __construct(private readonly string $appSecret)
    {
    }

    public function create(User $user): string
    {
        $payload = $this->base64UrlEncode(json_encode([
            'uid' => $user->getId(),
            'iat' => time(),
        ], JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $payload, $this->appSecret);

        return $payload.'.'.$signature;
    }

    public function userId(string $token): ?int
    {
        [$payload, $signature] = array_pad(explode('.', $token, 2), 2, null);
        if (!$payload || !$signature || !hash_equals(hash_hmac('sha256', $payload, $this->appSecret), $signature)) {
            return null;
        }

        $data = json_decode($this->base64UrlDecode($payload), true);

        return is_array($data) && isset($data['uid']) ? (int) $data['uid'] : null;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
