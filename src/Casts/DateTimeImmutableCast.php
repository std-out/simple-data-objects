<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use StdOut\SimpleDataObjects\Contracts\CastsValue;

final class DateTimeImmutableCast implements CastsValue
{
    private readonly ?string $timezone;

    public function __construct(
        private readonly string $outputFormat = DateTimeInterface::ATOM,
        private readonly ?string $inputFormat = null,
        DateTimeZone|string|null $timezone = null,
    ) {
        $this->timezone = $timezone instanceof DateTimeZone ? $timezone->getName() : $timezone;
    }

    public static function __set_state(array $state): self
    {
        return new self($state['outputFormat'], $state['inputFormat'], $state['timezone']);
    }

    public function get(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTime) {
            return DateTimeImmutable::createFromMutable($value);
        }

        $tz = $this->resolveTimezone();

        if ($this->inputFormat !== null) {
            $dt = DateTimeImmutable::createFromFormat($this->inputFormat, (string) $value, $tz);

            if ($dt === false) {
                throw new InvalidArgumentException(
                    "Cannot parse \"{$value}\" using format \"{$this->inputFormat}\".",
                );
            }

            return $dt;
        }

        return new DateTimeImmutable((string) $value, $tz);
    }

    public function set(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $dt = $value instanceof DateTimeInterface ? $value : $this->get($value);

        return $dt?->format($this->outputFormat);
    }

    private function resolveTimezone(): ?DateTimeZone
    {
        return $this->timezone !== null ? new DateTimeZone($this->timezone) : null;
    }
}
