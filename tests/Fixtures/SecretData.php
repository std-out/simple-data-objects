<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;

class SecretData extends BaseData
{
    public function __construct(
        #[Cast(new EncryptedCast('TU/pBcXXXWPceeZh0Zt76EgWHOl78yIlUaMoRPQZeME='))]
        public readonly string $token,
    ) {}
}
