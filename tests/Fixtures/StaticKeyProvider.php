<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Contracts\EncryptionKeyProvider;

final class StaticKeyProvider implements EncryptionKeyProvider
{
    public function key(): string
    {
        return 's/rAunJu2UVFegODXBdxxfC+RlDT66KL7yeKID87Hrw=';
    }
}
