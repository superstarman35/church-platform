<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Crypto
{
    private static function key(): string
    {
        $configured = (string) Config::get('app.key', '');
        if (str_starts_with($configured, 'base64:')) {
            $decoded = base64_decode(substr($configured, 7), true);
            if (is_string($decoded) && strlen($decoded) >= 32) {
                return substr($decoded, 0, 32);
            }
        }
        throw new RuntimeException('APP_KEY must be a base64 encoded 32-byte key. Run php bin/generate-key.php.');
    }

    public static function encrypt(string $plaintext): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if (!is_string($ciphertext)) {
            throw new RuntimeException('Unable to encrypt sensitive data.');
        }
        return base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(string $payload): string
    {
        $decoded = base64_decode($payload, true);
        if (!is_string($decoded) || strlen($decoded) < 29) {
            throw new RuntimeException('Invalid encrypted payload.');
        }
        $iv = substr($decoded, 0, 12);
        $tag = substr($decoded, 12, 16);
        $plaintext = openssl_decrypt(substr($decoded, 28), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if (!is_string($plaintext)) {
            throw new RuntimeException('Unable to decrypt sensitive data.');
        }
        return $plaintext;
    }
}
