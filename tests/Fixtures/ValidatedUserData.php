<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;

class ValidatedUserData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'string', 'max:100'])]
        public readonly string $name,

        #[Rules(['required', 'email:rfc'])]
        public readonly string $email,

        #[Rules(['nullable', 'string', 'min:6', 'max:20'])]
        public readonly ?string $username = null,
    ) {}
}
