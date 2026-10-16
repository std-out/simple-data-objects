<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Contracts\EncryptionKeyProvider;

final class EmptyKeyProvider implements EncryptionKeyProvider
{
    public function key(): string
    {
        return '';
    }
}
