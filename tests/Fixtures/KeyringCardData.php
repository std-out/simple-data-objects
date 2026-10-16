<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;

class KeyringCardData extends BaseData
{
    public function __construct(
        #[Cast(new EncryptedCast(keyring: TestKeyring::class))]
        public readonly string $pan,
        #[Cast(new EncryptedCast(keyring: TestKeyring::class))]
        public readonly string $holder,
        #[Cast(new EncryptedCast(keyring: TestKeyring::class, context: 'cards.note'))]
        public readonly ?string $note = null,
    ) {}
}
