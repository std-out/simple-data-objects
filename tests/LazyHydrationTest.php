<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use StdOut\SimpleDataObjects\Exceptions\DataHydrationException;
use StdOut\SimpleDataObjects\Tests\Fixtures\EmptyData;
use StdOut\SimpleDataObjects\Tests\Fixtures\EventData;
use StdOut\SimpleDataObjects\Tests\Fixtures\HybridData;
use StdOut\SimpleDataObjects\Tests\Fixtures\HybridPipedData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NoConstructorData;
use StdOut\SimpleDataObjects\Tests\Fixtures\NullifiedFormData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;

class LazyHydrationTest extends TestCase
{
    public function test_from_lazy_defers_hydration_until_first_access(): void
    {
        $user = UserData::fromLazy(['name' => 'Alice', 'email' => 'alice@example.com']);

        $reflector = new ReflectionClass(UserData::class);
        $this->assertTrue($reflector->isUninitializedLazyObject($user));

        $this->assertSame('Alice', $user->name);
        $this->assertFalse($reflector->isUninitializedLazyObject($user));
        $this->assertSame('alice@example.com', $user->email);
    }

    public function test_from_lazy_is_a_real_instance_of_the_class(): void
    {
        $user = UserData::fromLazy(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertInstanceOf(UserData::class, $user);
    }

    public function test_from_lazy_applies_casts_on_initialization(): void
    {
        $event = EventData::fromLazy(['name' => 'Conf', 'startsAt' => '2024-06-01']);

        $this->assertSame('2024-06-01', $event->startsAt->format('Y-m-d'));
    }

    public function test_from_lazy_runs_class_pipes_on_initialization(): void
    {
        $form = NullifiedFormData::fromLazy(['name' => '  hello  ', 'bio' => '']);

        $this->assertSame('hello', $form->name);
        $this->assertNull($form->bio);
    }

    public function test_from_lazy_serializes_transparently(): void
    {
        $user = UserData::fromLazy(['name' => 'Bob', 'email' => 'bob@example.com']);

        $this->assertSame(
            ['name' => 'Bob', 'email' => 'bob@example.com', 'phone' => null],
            $user->toArray(),
        );
    }

    public function test_from_lazy_normalizes_non_array_input(): void
    {
        $user = UserData::fromLazy((object) ['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertSame('Alice', $user->name);
    }

    public function test_from_lazy_invalid_data_throws_on_first_access_not_on_creation(): void
    {
        $user = UserData::fromLazy(['name' => 'Alice']); // missing required email — no throw yet

        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Missing required field 'email'/");

        $this->assertIsString($user->email); // access triggers deferred hydration
    }

    public function test_from_lazy_handles_class_without_constructor(): void
    {
        $data = EmptyData::fromLazy([]);

        $this->assertSame([], $data->toArray());
    }

    public function test_from_lazy_populates_declared_properties_for_class_without_constructor(): void
    {
        $data = NoConstructorData::fromLazy(['required' => 'r', 'id' => 'abc']);

        $reflector = new ReflectionClass(NoConstructorData::class);
        $this->assertTrue($reflector->isUninitializedLazyObject($data));

        $this->assertSame('r', $data->required);
        $this->assertFalse($reflector->isUninitializedLazyObject($data));
        $this->assertSame('abc', $data->id);
        $this->assertSame(1, $data->priority);
    }

    public function test_from_lazy_sets_readonly_property_for_class_without_constructor(): void
    {
        $data = NoConstructorData::fromLazy(['required' => 'r', 'id' => 'abc']);

        $this->assertSame('abc', $data->id);

        $this->expectException(\Error::class);
        $data->id = 'changed';
    }

    public function test_from_lazy_missing_required_field_throws_on_first_access_for_class_without_constructor(): void
    {
        $data = NoConstructorData::fromLazy(['id' => 'abc']); // missing required — no throw yet

        $this->expectException(DataHydrationException::class);
        $this->expectExceptionMessageMatches("/Missing required field 'required'/");

        $this->assertIsString($data->required);
    }

    public function test_from_lazy_populates_both_constructor_and_extra_properties_for_hybrid_class(): void
    {
        $data = HybridData::fromLazy(['id' => '1', 'extraId' => 'e1', 'extra_label' => 'Label']);

        $reflector = new ReflectionClass(HybridData::class);
        $this->assertTrue($reflector->isUninitializedLazyObject($data));

        $this->assertSame('1', $data->id);
        $this->assertFalse($reflector->isUninitializedLazyObject($data));
        $this->assertSame('e1', $data->extraId);
        $this->assertSame('Label', $data->extraLabel);
        $this->assertSame('new', $data->status);
    }

    public function test_from_lazy_sets_readonly_extra_property_for_hybrid_class(): void
    {
        $data = HybridData::fromLazy(['id' => '1', 'extraId' => 'e1', 'extra_label' => 'Label']);

        $this->assertSame('e1', $data->extraId);

        $this->expectException(\Error::class);
        $data->extraId = 'changed';
    }

    public function test_from_lazy_applies_class_pipes_to_both_constructor_and_extra_properties(): void
    {
        $data = HybridPipedData::fromLazy(['name' => '  Ada  ', 'bio' => '']);

        $this->assertSame('Ada', $data->name);
        $this->assertNull($data->bio);
    }

    public function test_from_lazy_reuses_compiled_arg_resolver(): void
    {
        $a = UserData::fromLazy(['name' => 'A', 'email' => 'a@example.com']);
        $b = UserData::fromLazy(['name' => 'B', 'email' => 'b@example.com']);

        $this->assertSame('A', $a->name);
        $this->assertSame('B', $b->name);
    }
}
