<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use Illuminate\Contracts\Database\Eloquent\Castable;
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;
use StdOut\SimpleDataObjects\Concerns\IsEloquentCastable;

final class CastableSecretData extends BaseData implements Castable
{
    use IsEloquentCastable;

    public function __construct(
        public readonly string $label,
        #[Cast(new EncryptedCast('test-encryption-key'))]
        public readonly string $pan,
    ) {}
}
