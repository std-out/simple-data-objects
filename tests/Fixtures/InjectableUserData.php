<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Concerns\HasLaravelIntegration;

class InjectableUserData extends BaseData
{
    use HasLaravelIntegration;

    public function __construct(
        #[Rules(['required', 'string'])]
        public readonly string $name,

        #[Rules(['required', 'email'])]
        public readonly string $email,
    ) {}
}
