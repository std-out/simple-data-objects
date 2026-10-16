<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Casts;

use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use StdOut\SimpleDataObjects\Contracts\CastsValue;
use StdOut\SimpleDataObjects\Contracts\ProvidesJsonSchema;
use StdOut\SimpleDataObjects\Support\Redactor;

final class DateTimeCast implements CastsValue, ProvidesJsonSchema
{
    private readonly ?string $timezone;

    public function __construct(
        private readonly string $outputFormat = DateTimeInterface::ATOM,
        private readonly ?string $inputFormat = null,
        DateTimeZone|string|null $timezone = null,
    ) {
        $this->timezone = $timezone instanceof DateTimeZone ? $timezone->getName() : $timezone;
    }

    /** @param  array<string, mixed>  $state */
    public static function __set_state(array $state): self
    {
        return new self($state['outputFormat'], $state['inputFormat'], $state['timezone']);
    }

    public function get(#[\SensitiveParameter] mixed $value): ?DateTime
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTime) {
            return $value;
        }

        if ($value instanceof DateTimeImmutable) {
            return DateTime::createFromImmutable($value);
        }

        $tz = $this->resolveTimezone();

        if ($this->inputFormat !== null) {
            $dt = DateTime::createFromFormat($this->inputFormat, (string) $value, $tz);

            if ($dt === false) {
                throw new InvalidArgumentException(
                    'Cannot parse "'.Redactor::forMessage((string) $value)."\" using format \"{$this->inputFormat}\".",
                );
            }

            return $dt;
        }

        try {
            return new DateTime((string) $value, $tz);
        } catch (DateMalformedStringException $e) {
            throw new DateMalformedStringException(Redactor::forMessage($e->getMessage()));
        }
    }

    public function set(#[\SensitiveParameter] mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $dt = $value instanceof DateTimeInterface ? $value : $this->get($value);

        return $dt?->format($this->outputFormat);
    }

    /** @return array<string, mixed> */
    public function jsonSchema(): array
    {
        return $this->outputFormat === DateTimeInterface::ATOM
            ? ['type' => 'string', 'format' => 'date-time']
            : ['type' => 'string'];
    }

    private function resolveTimezone(): ?DateTimeZone
    {
        return $this->timezone !== null ? new DateTimeZone($this->timezone) : null;
    }
}
