<?php

declare(strict_types=1);

namespace Velolia\Encryption;

use RuntimeException;

class Encrypter
{
    protected string $key;
    protected string $cipher = 'AES-256-CBC';

    public function __construct(string $key)
    {
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        if (strlen($key) !== 32) {
            throw new RuntimeException('App key must be 256 bits (32 characters).');
        }

        $this->key = $key;
    }

    public function encrypt(mixed $value): string
    {
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));

        $encrypted = openssl_encrypt(
            serialize($value),
            $this->cipher,
            $this->key,
            0,
            $iv
        );

        $iv        = base64_encode($iv);
        $mac       = $this->createMac($iv, $encrypted);

        return base64_encode(json_encode([
            'iv'    => $iv,
            'value' => $encrypted,
            'mac'   => $mac,
        ]));
    }

    public function decrypt(string $payload): mixed
    {
        $data = json_decode(base64_decode($payload), true);

        if (!isset($data['iv'], $data['value'], $data['mac'])) {
            throw new RuntimeException('Payload tidak valid.');
        }

        if (!$this->verifyMac($data['iv'], $data['value'], $data['mac'])) {
            throw new RuntimeException('MAC tidak valid. Data mungkin dimanipulasi.');
        }

        $decrypted = openssl_decrypt(
            $data['value'],
            $this->cipher,
            $this->key,
            0,
            base64_decode($data['iv'])
        );

        return unserialize($decrypted);
    }

    protected function createMac(string $iv, string $encrypted): string
    {
        return hash_hmac('sha256', $iv . $encrypted, $this->key);
    }

    protected function verifyMac(string $iv, string $encrypted, string $mac): bool
    {
        $expected = $this->createMac($iv, $encrypted);
        return hash_equals($expected, $mac);
    }
}