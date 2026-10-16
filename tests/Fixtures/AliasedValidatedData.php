<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\MapInputName;
use StdOut\SimpleDataObjects\Attributes\MapOutputName;
use StdOut\SimpleDataObjects\Attributes\Rules;
use StdOut\SimpleDataObjects\BaseData;

class AliasedValidatedData extends BaseData
{
    public function __construct(
        #[MapInputName('email', 'e_mail', 'mail')]
        #[Rules(['required', 'email:rfc'])]
        public readonly string $email,
        #[MapOutputName('card_number')]
        #[Rules(['sometimes', 'digits:16'])]
        public readonly ?string $cardNumber = null,
    ) {}
}
