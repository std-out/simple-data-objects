<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use InvalidArgumentException;
use RuntimeException;
use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class EncryptedCast implements CastsValue
{
    private readonly string $derivedKey;

    public function __construct(
        string $key,
        private readonly string $cipher = 'AES-256-CBC',
    ) {
        $this->derivedKey = hash('sha256', $key, true);
    }

    public function get(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $data = base64_decode((string) $value, strict: true);

        if ($data === false) {
            throw new InvalidArgumentException('Encrypted value is not valid base64.');
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $ciphertext = substr($data, $ivLength);

        $decrypted = openssl_decrypt($ciphertext, $this->cipher, $this->derivedKey, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new RuntimeException('Decryption failed.');
        }

        return $decrypted;
    }

    public function set(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = random_bytes($ivLength);
        $encrypted = openssl_encrypt((string) $value, $this->cipher, $this->derivedKey, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv.$encrypted);
    }
}
