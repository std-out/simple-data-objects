<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use InvalidArgumentException;
use LogicException;
use StdOut\SimpleDataObjects\Contracts\EncryptionKeyProvider;

final class BlindIndex
{
    private const string KDF_CONTEXT = 'SDOindex';

    private readonly \SensitiveParameterValue $key;

    private readonly string $prefix;

    /** @param class-string<EncryptionKeyProvider>|null $provider */
    public function __construct(
        #[\SensitiveParameter] ?string $key = null,
        ?string $env = null,
        ?string $provider = null,
        string $context = '',
    ) {
        $material = KeyMaterial::resolve($key, $env, $provider)
            ?? throw new InvalidArgumentException('Provide exactly one of $key, $env or $provider.');

        $this->key = new \SensitiveParameterValue(
            sodium_crypto_kdf_derive_from_key(KeyMaterial::BYTES, 1, self::KDF_CONTEXT, KeyMaterial::strict($material, 'Blind index key')),
        );
        $this->prefix = pack('N', strlen($context)).$context;
    }

    public function hash(#[\SensitiveParameter] string $value): string
    {
        return sodium_bin2base64(
            hash_hmac('sha256', $this->prefix.$value, $this->key->getValue(), true),
            SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING,
        );
    }

    public function matches(#[\SensitiveParameter] string $value, string $index): bool
    {
        return hash_equals($index, $this->hash($value));
    }

    public function __serialize(): array
    {
        throw new LogicException('BlindIndex holds key material and must not be serialized.');
    }

    public function __debugInfo(): array
    {
        return ['key' => Redactor::REDACTED];
    }
}
