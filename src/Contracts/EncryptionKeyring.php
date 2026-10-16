<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Contracts;

interface EncryptionKeyring
{
    public function currentKeyId(): string;

    /** @return array<string, string> */
    public function keys(): array;
}
