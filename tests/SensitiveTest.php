<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Concerns\HasSensitiveFields;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Support\ParameterMeta;
use StdOut\SimpleDataObjects\Tests\Fixtures\SensitiveCardData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SensitiveOptionalData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SensitiveSubclassData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SensitiveWithoutTraitData;
use StdOut\SimpleDataObjects\Tests\Fixtures\Status;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class SensitiveTest extends TestCase
{
    private const string PAN = '4111111111111111';

    public function test_explicit_output_keeps_sensitive_values(): void
    {
        $card = SensitiveCardData::from(['holder' => 'Alice', 'pan' => self::PAN, 'status' => 'active']);

        $this->assertSame(self::PAN, $card->pan);
        $this->assertSame(Status::Active, $card->status);
        $this->assertSame(self::PAN, $card->toArray()['pan']);
    }

    public function test_metadata_lists_sensitive_fields(): void
    {
        $this->assertSame(
            ['pan' => true, 'status' => true, 'cvv' => true],
            MetadataRegistry::get(SensitiveCardData::class)->sensitiveFields,
        );
        $this->assertSame([], MetadataRegistry::get(UserData::class)->sensitiveFields);
    }

    public function test_sensitive_flag_survives_the_file_cache_and_defaults_to_false(): void
    {
        $param = MetadataRegistry::get(SensitiveCardData::class)->parameters[1];

        $restored = eval('return '.var_export($param, true).';');

        $this->assertTrue($restored->isSensitive);

        $state = (array) $param;
        unset($state['isSensitive'], $state['isPlain']);

        $this->assertFalse(ParameterMeta::__set_state($state)->isSensitive);
    }

    public function test_serialize_refuses_a_set_sensitive_field(): void
    {
        $card = SensitiveCardData::from(['holder' => 'Alice', 'pan' => self::PAN]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot serialize StdOut\\SimpleDataObjects\\Tests\\Fixtures\\SensitiveCardData: field 'pan' is #[Sensitive].");

        serialize($card);
    }

    public function test_serialize_allows_null_sensitive_fields(): void
    {
        $data = SensitiveOptionalData::from(['holder' => 'Alice']);

        $this->assertTrue(unserialize(serialize($data))->equals($data));
    }

    public function test_debug_output_redacts_sensitive_fields(): void
    {
        $output = print_r(SensitiveCardData::from(['holder' => 'Alice', 'pan' => self::PAN, 'status' => 'active']), true);

        $this->assertStringNotContainsString(self::PAN, $output);
        $this->assertStringNotContainsString('Active', $output);
        $this->assertStringContainsString('[pan] => [redacted]', $output);
        $this->assertStringContainsString('[status] => [redacted]', $output);
        $this->assertStringContainsString('[cvv] => ', $output);
        $this->assertStringNotContainsString('[cvv] => [redacted]', $output);
        $this->assertStringContainsString('[holder] => Alice', $output);
        $this->assertStringContainsString('[note:'.SensitiveCardData::class.':private] => internal', $output);
    }

    public function test_classes_without_the_trait_keep_native_serialize_and_debug_output(): void
    {
        $this->assertFalse(method_exists(UserData::class, '__serialize'));
        $this->assertFalse(method_exists(UserData::class, '__debugInfo'));
    }

    public function test_sensitive_fields_require_the_trait(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(SensitiveWithoutTraitData::class.': #[Sensitive] fields require `use '.HasSensitiveFields::class.';`');

        SensitiveWithoutTraitData::from(['pan' => self::PAN]);
    }

    public function test_the_trait_is_inherited(): void
    {
        $data = SensitiveSubclassData::from(['holder' => 'Alice', 'cvv' => '123']);

        $this->assertStringContainsString('[cvv] => [redacted]', print_r($data, true));
    }

    public function test_serialize_initializes_a_lazy_object(): void
    {
        $lazy = SensitiveOptionalData::fromLazy(['holder' => 'Alice']);

        $this->assertSame('Alice', unserialize(serialize($lazy))->holder);
    }

    public function test_debug_output_does_not_initialize_a_lazy_object(): void
    {
        $lazy = SensitiveCardData::fromLazy(['holder' => 'Alice']);

        $this->assertSame(SensitiveCardData::class." Object\n(\n)\n", print_r($lazy, true));
        $this->assertTrue((new \ReflectionClass(SensitiveCardData::class))->isUninitializedLazyObject($lazy));
    }

    public function test_invalid_sensitive_value_is_not_echoed_by_from(): void
    {
        try {
            SensitiveCardData::from(['holder' => 'Alice', 'pan' => self::PAN, 'status' => 'secret-4242']);
            $this->fail('Expected DataHydrationException.');
        } catch (DataHydrationException $e) {
            $this->assertSame("Invalid value for sensitive field 'status' of ".SensitiveCardData::class.'.', $e->getMessage());
            $this->assertNull($e->getPrevious());
        }
    }

    public function test_invalid_sensitive_value_is_not_echoed_by_from_result(): void
    {
        $result = SensitiveCardData::fromResult(['holder' => 'Alice', 'pan' => self::PAN, 'status' => 'secret-4242']);

        $this->assertSame(
            ['status' => "Invalid value for sensitive field 'status' of ".SensitiveCardData::class.'.'],
            $result->errors(),
        );
    }

    public function test_invalid_sensitive_value_is_not_echoed_by_with(): void
    {
        $card = SensitiveCardData::from(['holder' => 'Alice', 'pan' => self::PAN]);

        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessage("Invalid value for sensitive field 'status' of ".SensitiveCardData::class.'.');

        $card->with(status: 'secret-4242');
    }

    public function test_with_casts_sensitive_and_plain_fields(): void
    {
        $card = SensitiveCardData::from(['holder' => 'Alice', 'pan' => self::PAN])->with(status: 'inactive', holder: 'Bob');

        $this->assertSame(Status::Inactive, $card->status);
        $this->assertSame('Bob', $card->holder);
    }
}
