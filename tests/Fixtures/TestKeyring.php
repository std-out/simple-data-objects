<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Contracts\EncryptionKeyring;

final class TestKeyring implements EncryptionKeyring
{
    public function currentKeyId(): string
    {
        return '2026';
    }

    public function keys(): array
    {
        return [
            '2025' => str_repeat("\x01", 32),
            '2026' => base64_encode(str_repeat("\x02", 32)),
        ];
    }
}
