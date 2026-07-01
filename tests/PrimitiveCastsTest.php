<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Casts\BooleanCast;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;
use StdOut\SimpleDataObjects\Casts\EnumCast;
use StdOut\SimpleDataObjects\Casts\FloatCast;
use StdOut\SimpleDataObjects\Casts\IntegerCast;
use StdOut\SimpleDataObjects\Casts\JsonCast;
use StdOut\SimpleDataObjects\Casts\TrimCast;
use StdOut\SimpleDataObjects\Tests\Fixtures\Priority;
use StdOut\SimpleDataObjects\Tests\Fixtures\ProductData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SecretData;
use StdOut\SimpleDataObjects\Tests\Fixtures\Status;

class PrimitiveCastsTest extends TestCase
{
    // IntegerCast

    public function test_integer_cast_converts_string_to_int(): void
    {
        $this->assertSame(42, (new IntegerCast)->get('42'));
    }

    public function test_integer_cast_converts_float_to_int(): void
    {
        $this->assertSame(12, (new IntegerCast)->get(12.9));
    }

    public function test_integer_cast_null_returns_null(): void
    {
        $this->assertNull((new IntegerCast)->get(null));
        $this->assertNull((new IntegerCast)->set(null));
    }

    public function test_integer_cast_hydrates_via_dto(): void
    {
        $product = ProductData::from(['sku' => 'abc', 'quantity' => '5', 'price' => '9.99', 'available' => '1', 'meta' => '{}']);

        $this->assertSame(5, $product->quantity);
        $this->assertIsInt($product->quantity);
    }

    // FloatCast

    public function test_float_cast_converts_string_to_float(): void
    {
        $this->assertSame(12.99, (new FloatCast)->get('12.99'));
    }

    public function test_float_cast_rounds_to_decimals(): void
    {
        $this->assertSame(12.99, (new FloatCast(2))->get('12.9876'));
    }

    public function test_float_cast_null_returns_null(): void
    {
        $this->assertNull((new FloatCast)->get(null));
    }

    public function test_float_cast_hydrates_via_dto(): void
    {
        $product = ProductData::from(['sku' => 'abc', 'quantity' => '5', 'price' => '9.9876', 'available' => '1', 'meta' => '{}']);

        $this->assertSame(9.99, $product->price);
    }

    // BooleanCast

    public function test_boolean_cast_truthy_strings(): void
    {
        $cast = new BooleanCast;

        $this->assertTrue($cast->get('true'));
        $this->assertTrue($cast->get('1'));
        $this->assertTrue($cast->get('yes'));
        $this->assertTrue($cast->get('on'));
        $this->assertTrue($cast->get('TRUE'));
    }

    public function test_boolean_cast_falsy_strings(): void
    {
        $cast = new BooleanCast;

        $this->assertFalse($cast->get('false'));
        $this->assertFalse($cast->get('0'));
        $this->assertFalse($cast->get('no'));
        $this->assertFalse($cast->get('off'));
    }

    public function test_boolean_cast_passes_through_bool(): void
    {
        $cast = new BooleanCast;

        $this->assertTrue($cast->get(true));
        $this->assertFalse($cast->get(false));
    }

    public function test_boolean_cast_null_returns_null(): void
    {
        $this->assertNull((new BooleanCast)->get(null));
    }

    public function test_boolean_cast_hydrates_via_dto(): void
    {
        $product = ProductData::from(['sku' => 'abc', 'quantity' => '5', 'price' => '9.99', 'available' => 'yes', 'meta' => '{}']);

        $this->assertTrue($product->available);
    }

    // TrimCast

    public function test_trim_cast_removes_whitespace(): void
    {
        $this->assertSame('hello', (new TrimCast)->get('  hello  '));
    }

    public function test_trim_cast_lowercase(): void
    {
        $this->assertSame('hello world', (new TrimCast(TrimCast::LOWERCASE))->get('  Hello World  '));
    }

    public function test_trim_cast_uppercase(): void
    {
        $this->assertSame('HELLO WORLD', (new TrimCast(TrimCast::UPPERCASE))->get('  hello world  '));
    }

    public function test_trim_cast_null_returns_null(): void
    {
        $this->assertNull((new TrimCast)->get(null));
    }

    public function test_trim_cast_serializes_trimmed(): void
    {
        $product = ProductData::from(['sku' => '  ABC-123  ', 'quantity' => '5', 'price' => '9.99', 'available' => '1', 'meta' => '{}']);

        $this->assertSame('abc-123', $product->sku);
        $this->assertSame('abc-123', $product->toArray()['sku']);
    }

    // JsonCast

    public function test_json_cast_decodes_string_to_array(): void
    {
        $result = (new JsonCast)->get('{"key":"value"}');

        $this->assertSame(['key' => 'value'], $result);
    }

    public function test_json_cast_encodes_array_to_string(): void
    {
        $result = (new JsonCast)->set(['key' => 'value']);

        $this->assertSame('{"key":"value"}', $result);
    }

    public function test_json_cast_passes_through_array(): void
    {
        $array = ['key' => 'value'];

        $this->assertSame($array, (new JsonCast)->get($array));
    }

    public function test_json_cast_null_returns_null(): void
    {
        $this->assertNull((new JsonCast)->get(null));
        $this->assertNull((new JsonCast)->set(null));
    }

    public function test_json_cast_invalid_json_throws(): void
    {
        $this->expectException(JsonException::class);

        (new JsonCast)->get('not-json');
    }

    public function test_json_cast_hydrates_and_serializes_via_dto(): void
    {
        $product = ProductData::from(['sku' => 'abc', 'quantity' => '5', 'price' => '9.99', 'available' => '1', 'meta' => '{"color":"red"}']);

        $this->assertSame(['color' => 'red'], $product->meta);
        $this->assertSame('{"color":"red"}', $product->toArray()['meta']);
    }

    // EncryptedCast — XSalsa20-Poly1305 via libsodium (authenticated AEAD)

    public function test_encrypted_cast_round_trip(): void
    {
        $cast = new EncryptedCast('my-key');
        $encrypted = $cast->set('secret-token');

        $this->assertNotNull($encrypted);
        $this->assertNotSame('secret-token', $encrypted);
        $this->assertSame('secret-token', $cast->get($encrypted));
    }

    public function test_encrypted_cast_produces_different_output_each_time(): void
    {
        $cast = new EncryptedCast('my-key');

        $this->assertNotSame($cast->set('value'), $cast->set('value'));
    }

    public function test_encrypted_cast_null_returns_null(): void
    {
        $cast = new EncryptedCast('my-key');

        $this->assertNull($cast->get(null));
        $this->assertNull($cast->set(null));
    }

    public function test_encrypted_cast_invalid_base64_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new EncryptedCast('my-key'))->get('not!!valid!!base64!!####');
    }

    public function test_encrypted_cast_too_short_value_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('too short');

        // 10 bytes < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES (24)
        $short = sodium_bin2base64(str_repeat('x', 10), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        (new EncryptedCast('my-key'))->get($short);
    }

    public function test_encrypted_cast_tampered_ciphertext_throws(): void
    {
        $cast = new EncryptedCast('my-key');
        $encrypted = $cast->set('secret');

        $decoded = sodium_base642bin($encrypted, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        $tampered = substr($decoded, 0, -1).chr(ord(substr($decoded, -1)) ^ 0xFF);
        $tamperedB64 = sodium_bin2base64($tampered, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);

        $this->expectException(\RuntimeException::class);
        $cast->get($tamperedB64);
    }

    public function test_encrypted_cast_different_keys_cannot_decrypt(): void
    {
        $encrypted = (new EncryptedCast('key-A'))->set('secret');

        $this->expectException(\RuntimeException::class);
        (new EncryptedCast('key-B'))->get($encrypted);
    }

    public function test_encrypted_cast_output_is_url_safe_base64(): void
    {
        $encrypted = (new EncryptedCast('my-key'))->set('hello');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $encrypted);
    }

    public function test_encrypted_cast_hydrates_and_decrypts_via_dto(): void
    {
        $cast = new EncryptedCast('test-encryption-key');
        $encrypted = $cast->set('my-api-token');

        $secret = SecretData::from(['token' => $encrypted]);

        $this->assertSame('my-api-token', $secret->token);
    }

    public function test_encrypted_cast_serializes_on_to_array(): void
    {
        $secret = SecretData::from(['token' => (new EncryptedCast('test-encryption-key'))->set('my-api-token')]);
        $array = $secret->toArray();

        $this->assertNotSame('my-api-token', $array['token']);
        $this->assertSame('my-api-token', (new EncryptedCast('test-encryption-key'))->get($array['token']));
    }

    // EnumCast::set() missing paths

    public function test_enum_cast_set_returns_null_for_null(): void
    {
        $cast = new EnumCast(Status::class, Status::Inactive);

        $this->assertNull($cast->set(null));
    }

    public function test_enum_cast_set_returns_name_for_pure_unit_enum(): void
    {
        $cast = new EnumCast(Priority::class);

        $this->assertSame('High', $cast->set(Priority::High));
    }

    public function test_enum_cast_set_passes_through_non_enum_value(): void
    {
        $cast = new EnumCast(Status::class);

        $this->assertSame('raw_value', $cast->set('raw_value'));
    }

    public function test_enum_cast_get_returns_default_for_pure_unit_enum_string_value(): void
    {
        $cast = new EnumCast(Priority::class, Priority::Low);

        $this->assertSame(Priority::Low, $cast->get('anything'));
    }

    public function test_boolean_cast_set_returns_null_for_null(): void
    {
        $this->assertNull((new BooleanCast)->set(null));
    }

    public function test_float_cast_set_delegates_to_get(): void
    {
        $cast = new FloatCast(2);

        $this->assertSame(3.14, $cast->set(3.14159));
    }
}
