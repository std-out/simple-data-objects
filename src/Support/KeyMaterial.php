<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use InvalidArgumentException;
use RuntimeException;
use StdOut\SimpleDataObjects\Contracts\EncryptionKeyProvider;

final class KeyMaterial
{
    public const int BYTES = 32;

    public static function resolve(#[\SensitiveParameter] ?string $key, ?string $env, ?string $provider): ?string
    {
        return match (true) {
            ($key !== null) + ($env !== null) + ($provider !== null) > 1 => throw new InvalidArgumentException('Provide exactly one of $key, $env or $provider.'),
            $env !== null => self::fromEnvironment($env),
            $provider !== null => self::fromProvider($provider),
            default => $key,
        };
    }

    public static function strict(#[\SensitiveParameter] string $key, string $label): string
    {
        if (strlen($key) === self::BYTES && ! ctype_print($key)) {
            return $key;
        }

        $decoded = base64_decode(strtr($key, '-_', '+/'), true);

        if ($decoded !== false && strlen($decoded) === self::BYTES) {
            return $decoded;
        }

        throw new InvalidArgumentException("{$label} must be 32 random bytes, raw or base64-encoded.");
    }

    private static function fromEnvironment(string $env): string
    {
        $key = $_ENV[$env] ?? getenv($env);

        if (! is_string($key) || $key === '') {
            throw new RuntimeException("Environment variable '{$env}' is not set or empty.");
        }

        return $key;
    }

    private static function fromProvider(string $provider): string
    {
        if (! is_subclass_of($provider, EncryptionKeyProvider::class)) {
            throw new InvalidArgumentException("Key provider '{$provider}' must implement ".EncryptionKeyProvider::class.'.');
        }

        $key = (new $provider)->key();

        if ($key === '') {
            throw new RuntimeException("Key provider '{$provider}' returned an empty key.");
        }

        return $key;
    }
}
