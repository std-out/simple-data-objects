<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use Illuminate\Contracts\Database\Eloquent\Castable;
use StdOut\SimpleDataObjects\Attributes\Hidden;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\IsEloquentCastable;

final class CastableAccountData extends BaseData implements Castable
{
    use IsEloquentCastable;

    public function __construct(
        public readonly string $login,
        #[Hidden(except: ['storage'])]
        public readonly string $token,
    ) {}
}
