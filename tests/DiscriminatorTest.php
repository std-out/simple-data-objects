<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Support\ClassMetaFactory;
use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Tests\Fixtures\AbstractTargetDiscriminatorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\BankPaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\BikeData;
use StdOut\SimpleDataObjects\Tests\Fixtures\CardPaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ChannelData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ConcreteDiscriminatorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\EmailChannelData;
use StdOut\SimpleDataObjects\Tests\Fixtures\EmptyMapDiscriminatorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ForeignTargetDiscriminatorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\GenericChannelData;
use StdOut\SimpleDataObjects\Tests\Fixtures\MissingTargetDiscriminatorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NonStringTargetDiscriminatorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OrderWithPaymentData;
use StdOut\SimpleDataObjects\Tests\Fixtures\OtherThingData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PaymentMethodData;
use StdOut\SimpleDataObjects\Tests\Fixtures\PaymentType;
use StdOut\SimpleDataObjects\Tests\Fixtures\PipedDiscriminatorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\ThingData;

class DiscriminatorTest extends TestCase
{
    public function test_from_resolves_mapped_subclass(): void
    {
        $payment = PaymentMethodData::from(['type' => 'card', 'amount' => 100, 'last4' => '4242']);

        $this->assertInstanceOf(CardPaymentData::class, $payment);
        $this->assertSame(100, $payment->amount);
        $this->assertSame('4242', $payment->last4);
        $this->assertSame(PaymentType::Card, $payment->type);
    }

    public function test_from_resolves_constructor_less_subclass(): void
    {
        $payment = PaymentMethodData::from(['type' => 'bank', 'amount' => 250, 'iban' => 'UA903052992990004149123456789']);

        $this->assertInstanceOf(BankPaymentData::class, $payment);
        $this->assertSame(250, $payment->amount);
        $this->assertSame('UA903052992990004149123456789', $payment->iban);
    }

    public function test_from_returns_existing_subclass_instance_as_is(): void
    {
        $card = CardPaymentData::from(['amount' => 1, 'last4' => '0001']);

        $this->assertSame($card, PaymentMethodData::from($card));
    }

    public function test_from_accepts_json_and_object_input(): void
    {
        $fromJson = PaymentMethodData::from('{"type":"card","amount":7,"last4":"7777"}');
        $fromObject = PaymentMethodData::from((object) ['type' => 'bank', 'amount' => 3, 'iban' => 'UA1']);

        $this->assertInstanceOf(CardPaymentData::class, $fromJson);
        $this->assertInstanceOf(BankPaymentData::class, $fromObject);
    }

    public function test_backed_enum_discriminator_value_resolves(): void
    {
        $payment = PaymentMethodData::from(['type' => PaymentType::Card, 'amount' => 5, 'last4' => '5555']);

        $this->assertInstanceOf(CardPaymentData::class, $payment);
        $this->assertSame(PaymentType::Card, $payment->type);
    }

    public function test_round_trip_from_to_array(): void
    {
        $original = PaymentMethodData::from(['type' => 'card', 'amount' => 100, 'last4' => '4242']);
        $arr = $original->toArray();

        $this->assertSame('card', $arr['type']);
        $this->assertTrue($original->equals(PaymentMethodData::from($arr)));
    }

    public function test_missing_discriminator_field_throws(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Missing discriminator field 'type'/");

        PaymentMethodData::from(['amount' => 100, 'last4' => '4242']);
    }

    public function test_unknown_discriminator_value_throws(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Unknown discriminator value 'crypto' in field 'type'/");

        PaymentMethodData::from(['type' => 'crypto', 'amount' => 100]);
    }

    public function test_non_scalar_discriminator_value_throws(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Unknown discriminator value array in field 'type'/");

        PaymentMethodData::from(['type' => ['card'], 'amount' => 100]);
    }

    public function test_try_from_returns_null_on_unknown_value(): void
    {
        $this->assertNull(PaymentMethodData::tryFrom(['type' => 'crypto']));
    }

    public function test_fallback_class_handles_unknown_value(): void
    {
        $channel = ChannelData::from(['channel' => 'sms', 'payload' => '+380501234567']);

        $this->assertInstanceOf(GenericChannelData::class, $channel);
        $this->assertSame('sms', $channel->channel);
    }

    public function test_fallback_class_handles_missing_field(): void
    {
        $channel = ChannelData::from(['payload' => 'hello']);

        $this->assertInstanceOf(GenericChannelData::class, $channel);
        $this->assertNull($channel->channel);
    }

    public function test_mapped_value_wins_over_fallback(): void
    {
        $channel = ChannelData::from(['channel' => 'email', 'address' => 'a@b.co']);

        $this->assertInstanceOf(EmailChannelData::class, $channel);
    }

    public function test_multi_level_hierarchy_dispatches_through_intermediate_class(): void
    {
        $bike = ThingData::from(['category' => 'vehicle', 'wheels' => 2, 'electric' => true]);
        $other = ThingData::from(['category' => 'other', 'note' => 'rock']);

        $this->assertInstanceOf(BikeData::class, $bike);
        $this->assertTrue($bike->electric);
        $this->assertInstanceOf(OtherThingData::class, $other);
    }

    public function test_integer_map_keys_match_numeric_string_input(): void
    {
        $bike = ThingData::from(['category' => 'vehicle', 'wheels' => '2']);

        $this->assertInstanceOf(BikeData::class, $bike);
    }

    public function test_collection_hydrates_mixed_subclasses(): void
    {
        $payments = PaymentMethodData::collection([
            ['type' => 'card', 'amount' => 1, 'last4' => '0001'],
            ['type' => 'bank', 'amount' => 2, 'iban' => 'UA2'],
        ]);

        $this->assertInstanceOf(CardPaymentData::class, $payments[0]);
        $this->assertInstanceOf(BankPaymentData::class, $payments[1]);
    }

    public function test_lazy_collection_hydrates_mixed_subclasses(): void
    {
        $payments = PaymentMethodData::lazyCollection([
            ['type' => 'card', 'amount' => 1, 'last4' => '0001'],
            ['type' => 'bank', 'amount' => 2, 'iban' => 'UA2'],
        ])->all();

        $this->assertInstanceOf(CardPaymentData::class, $payments[0]);
        $this->assertInstanceOf(BankPaymentData::class, $payments[1]);
    }

    public function test_nested_property_typed_as_discriminated_base(): void
    {
        $order = OrderWithPaymentData::from([
            'id' => 'ord-1',
            'payment' => ['type' => 'bank', 'amount' => 9, 'iban' => 'UA3'],
            'history' => [
                ['type' => 'card', 'amount' => 1, 'last4' => '0001'],
                ['type' => 'bank', 'amount' => 2, 'iban' => 'UA4'],
            ],
        ]);

        $this->assertInstanceOf(BankPaymentData::class, $order->payment);
        $this->assertInstanceOf(CardPaymentData::class, $order->history[0]);
        $this->assertInstanceOf(BankPaymentData::class, $order->history[1]);
    }

    public function test_from_lazy_resolves_concrete_class_eagerly(): void
    {
        $payment = PaymentMethodData::fromLazy(['type' => 'card', 'amount' => 42, 'last4' => '4242']);

        $this->assertInstanceOf(CardPaymentData::class, $payment);
        $this->assertTrue(new ReflectionClass(CardPaymentData::class)->isUninitializedLazyObject($payment));
        $this->assertSame(42, $payment->amount);
    }

    public function test_from_lazy_accepts_json_input(): void
    {
        $payment = PaymentMethodData::fromLazy('{"type":"bank","amount":8,"iban":"UA5"}');

        $this->assertInstanceOf(BankPaymentData::class, $payment);
        $this->assertSame('UA5', $payment->iban);
    }

    public function test_from_lazy_throws_eagerly_on_unresolved_discriminator(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Missing discriminator field 'type'/");

        PaymentMethodData::fromLazy(['amount' => 1]);
    }

    public function test_from_validated_applies_concrete_class_rules(): void
    {
        $this->expectException(ValidationException::class);

        PaymentMethodData::fromValidated(['type' => 'card', 'amount' => 1, 'last4' => '12345']);
    }

    public function test_from_validated_returns_concrete_instance_on_valid_input(): void
    {
        $payment = PaymentMethodData::fromValidated(['type' => 'card', 'amount' => 1, 'last4' => '1234']);

        $this->assertInstanceOf(CardPaymentData::class, $payment);
    }

    public function test_validate_delegates_to_concrete_class(): void
    {
        $this->expectException(ValidationException::class);

        PaymentMethodData::validate(['type' => 'card', 'amount' => 1, 'last4' => '']);
    }

    public function test_validate_passes_for_valid_input(): void
    {
        PaymentMethodData::validate(['type' => 'card', 'amount' => 1, 'last4' => '1234']);
        PaymentMethodData::validate(['type' => 'bank', 'amount' => 1]);

        $this->addToAssertionCount(1);
    }

    public function test_validate_throws_for_unresolved_discriminator(): void
    {
        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Unknown discriminator value 'crypto'/");

        PaymentMethodData::validate(['type' => 'crypto']);
    }

    public function test_non_abstract_class_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/requires an abstract class/');

        ClassMetaFactory::build(ConcreteDiscriminatorData::class);
    }

    public function test_empty_map_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/map cannot be empty/');

        ClassMetaFactory::build(EmptyMapDiscriminatorData::class);
    }

    public function test_missing_target_class_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/is not an existing class/');

        ClassMetaFactory::build(MissingTargetDiscriminatorData::class);
    }

    public function test_non_string_target_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/target int is not an existing class/');

        ClassMetaFactory::build(NonStringTargetDiscriminatorData::class);
    }

    public function test_target_outside_the_hierarchy_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must extend/');

        ClassMetaFactory::build(ForeignTargetDiscriminatorData::class);
    }

    public function test_abstract_target_without_own_discriminator_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must declare its own #\[Discriminator\]/');

        ClassMetaFactory::build(AbstractTargetDiscriminatorData::class);
    }

    public function test_class_level_pipe_cannot_be_combined_with_discriminator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/#\[Discriminator\] and class-level #\[Pipe\] cannot be combined/');

        ClassMetaFactory::build(PipedDiscriminatorData::class);
    }

    public function test_dispatcher_survives_metadata_cache_round_trip(): void
    {
        $cacheDir = sys_get_temp_dir().'/sdo_discriminator_cache_'.uniqid();
        mkdir($cacheDir, 0755, true);

        try {
            MetadataRegistry::flush();
            MetadataRegistry::setStoragePath($cacheDir);

            $first = PaymentMethodData::from(['type' => 'card', 'amount' => 1, 'last4' => '0001']);
            $this->assertInstanceOf(CardPaymentData::class, $first);
            $this->assertTrue(MetadataRegistry::isPersisted(PaymentMethodData::class));

            // Simulate a fresh process: in-memory caches gone, files remain
            MetadataRegistry::flush();

            $second = PaymentMethodData::from(['type' => 'bank', 'amount' => 2, 'iban' => 'UA6']);
            $this->assertInstanceOf(BankPaymentData::class, $second);
        } finally {
            MetadataRegistry::clearCache();
            MetadataRegistry::setStoragePath('');
            MetadataRegistry::flush();

            foreach (glob($cacheDir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($cacheDir);
        }
    }
}
