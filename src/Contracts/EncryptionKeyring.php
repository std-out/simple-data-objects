<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Contracts;

interface EncryptionKeyring
{
    public function currentKeyId(): string;

    /** @return array<array-key, string> */
    public function keys(): array;
}
