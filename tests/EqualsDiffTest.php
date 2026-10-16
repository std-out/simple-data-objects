<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests;

use PHPUnit\Framework\TestCase;
use StdOut\SimpleDataObjects\Casts\EncryptedCast;
use StdOut\SimpleDataObjects\Tests\Fixtures\EventData;
use StdOut\SimpleDataObjects\Tests\Fixtures\KeyringCardData;
use StdOut\SimpleDataObjects\Tests\Fixtures\SecretData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserData;
use StdOut\SimpleDataObjects\Tests\Fixtures\UserDataSubclass;

class EqualsDiffTest extends TestCase
{
    public function test_equals_returns_true_for_identical_data(): void
    {
        $a = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $b = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_data(): void
    {
        $a = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $b = UserData::from(['name' => 'Bob', 'email' => 'alice@example.com']);

        $this->assertFalse($a->equals($b));
    }

    public function test_equals_is_commutative(): void
    {
        $a = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $b = UserData::from(['name' => 'Bob', 'email' => 'bob@example.com']);

        $this->assertSame($a->equals($b), $b->equals($a));
    }

    public function test_equals_reflexive(): void
    {
        $a = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertTrue($a->equals($a));
    }

    public function test_diff_returns_empty_for_equal_objects(): void
    {
        $a = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $b = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);

        $this->assertSame([], $a->diff($b));
    }

    public function test_diff_returns_changed_fields(): void
    {
        $a = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $b = UserData::from(['name' => 'Bob', 'email' => 'alice@example.com']);

        $diff = $a->diff($b);

        $this->assertArrayHasKey('name', $diff);
        $this->assertArrayNotHasKey('email', $diff);
    }

    public function test_diff_contains_both_values(): void
    {
        $a = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $b = UserData::from(['name' => 'Bob', 'email' => 'bob@example.com']);

        $diff = $a->diff($b);

        $this->assertSame(['Alice', 'Bob'], $diff['name']);
        $this->assertSame(['alice@example.com', 'bob@example.com'], $diff['email']);
    }

    public function test_diff_with_null_vs_value(): void
    {
        $a = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $b = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com', 'phone' => '123']);

        $diff = $a->diff($b);

        $this->assertArrayHasKey('phone', $diff);
        $this->assertSame([null, '123'], $diff['phone']);
    }

    public function test_diff_with_casted_fields(): void
    {
        $a = EventData::from(['name' => 'Conf', 'startsAt' => '2024-01-01']);
        $b = EventData::from(['name' => 'Conf', 'startsAt' => '2025-06-15']);

        $diff = $a->diff($b);

        $this->assertArrayHasKey('startsAt', $diff);
        $this->assertSame('2024-01-01', $diff['startsAt'][0]);
        $this->assertSame('2025-06-15', $diff['startsAt'][1]);
    }

    public function test_diff_multiple_changes(): void
    {
        $a = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com', 'phone' => '111']);
        $b = UserData::from(['name' => 'Bob', 'email' => 'bob@example.com', 'phone' => '222']);

        $diff = $a->diff($b);

        $this->assertCount(3, $diff);
        $this->assertArrayHasKey('name', $diff);
        $this->assertArrayHasKey('email', $diff);
        $this->assertArrayHasKey('phone', $diff);
    }

    public function test_equals_returns_false_for_different_classes(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $event = EventData::from(['name' => 'Conf', 'startsAt' => '2024-06-01']);

        $this->assertFalse($user->equals($event));
    }

    public function test_equals_compares_encrypted_fields_by_plaintext(): void
    {
        $a = new SecretData('abc');

        $this->assertNotSame($a->toArray(), new SecretData('abc')->toArray());
        $this->assertTrue($a->equals(new SecretData('abc')));
        $this->assertFalse($a->equals(new SecretData('abd')));
    }

    public function test_diff_ignores_reencryption_and_reports_ciphertext_of_changed_fields(): void
    {
        $a = new KeyringCardData('4111111111111111', 'ALICE');
        $b = new KeyringCardData('4111111111111111', 'BOB');

        $diff = $a->diff($b);

        $this->assertSame(['holder'], array_keys($diff));
        $this->assertStringStartsWith('v2:', $diff['holder'][0]);
        $this->assertStringStartsWith('v2:', $diff['holder'][1]);
        $this->assertSame([], $a->diff(new KeyringCardData('4111111111111111', 'ALICE')));
    }

    public function test_plaintext_comparison_mode_is_scoped_and_restored(): void
    {
        $cast = new EncryptedCast('test-encryption-key');

        $inner = EncryptedCast::comparingPlaintext(static function () use ($cast): array {
            $nested = EncryptedCast::comparingPlaintext(static fn (): ?string => $cast->set('x'));

            return [$nested, $cast->set('y')];
        });

        $this->assertSame(['x', 'y'], $inner);
        $this->assertNotSame('z', $cast->set('z'));

        try {
            EncryptedCast::comparingPlaintext(static fn () => throw new \RuntimeException('boom'));
        } catch (\RuntimeException) {
        }

        $this->assertNotSame('z', $cast->set('z'));
    }

    public function test_equals_returns_false_for_different_classes_with_identical_output(): void
    {
        $user = UserData::from(['name' => 'Alice', 'email' => 'alice@example.com']);
        $admin = new UserDataSubclass('Alice', 'alice@example.com');

        $this->assertSame($user->toArray(), $admin->toArray());
        $this->assertFalse($user->equals($admin));
    }
}
