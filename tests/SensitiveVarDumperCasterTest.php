<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Support\SensitiveVarDumperCaster;
use StdOut\SimpleDataObjects\Tests\Fixtures\SensitiveCardData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SensitiveOptionalData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SensitivePrivateData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class SensitiveVarDumperCasterTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $casters;

    protected function setUp(): void
    {
        $this->casters = AbstractCloner::$defaultCasters;
    }

    protected function tearDown(): void
    {
        AbstractCloner::$defaultCasters = $this->casters;
    }

    private static function dump(mixed $value): string
    {
        return (string) (new CliDumper)->dump((new VarCloner)->cloneVar($value), true);
    }

    public function test_without_registration_var_dumper_shows_the_value(): void
    {
        $this->assertStringContainsString('4111111111111111', self::dump(new SensitiveCardData('Alice', '4111111111111111')));
    }

    public function test_registered_caster_redacts_sensitive_fields_at_every_level(): void
    {
        SensitiveVarDumperCaster::register();

        $dump = self::dump([
            new SensitiveCardData('Alice', '4111111111111111', cvv: '123'),
            new SensitivePrivateData('Bob', '9876'),
        ]);

        $this->assertStringNotContainsString('4111111111111111', $dump);
        $this->assertStringNotContainsString('9876', $dump);
        $this->assertStringNotContainsString('"123"', $dump);
        $this->assertStringContainsString('Alice', $dump);
        $this->assertStringContainsString('[redacted]', $dump);
    }

    public function test_null_sensitive_fields_and_plain_dtos_are_left_alone(): void
    {
        SensitiveVarDumperCaster::register();

        $this->assertMatchesRegularExpression('/\+cvv: null/', self::dump(new SensitiveOptionalData('Alice')));
        $this->assertStringContainsString('a@example.com', self::dump(new UserData('a', 'a@example.com')));
    }
}
