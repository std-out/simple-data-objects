<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Contracts;

interface EncryptionKeyProvider
{
    public function key(): string;
}
