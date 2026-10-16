<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Contracts\EncryptionKeyring;

final class ConfigurableKeyring implements EncryptionKeyring
{
    public static string $current = 'k1';

    /** @var array<array-key, string> */
    public static array $keys = [];

    public function currentKeyId(): string
    {
        return self::$current;
    }

    public function keys(): array
    {
        return self::$keys;
    }
}
