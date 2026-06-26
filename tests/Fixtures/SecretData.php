<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;

class SecretData extends BaseData
{
    public function __construct(
        #[Cast(new EncryptedCast('test-encryption-key'))]
        public readonly string $token,
    ) {}
}
