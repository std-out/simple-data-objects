<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\BaseData;

/** Validates in its own constructor, so a rejected card is thrown from that frame. */
class ChargeData extends BaseData
{
    public function __construct(
        public readonly string $pan,
        public readonly int $amount,
        #[\SensitiveParameter]
        public readonly string $cvv = '',
    ) {
        if ($amount < 0) {
            throw new \DomainException('Charge rejected.', 0, new \InvalidArgumentException('Amount must not be negative.'));
        }
    }
}
