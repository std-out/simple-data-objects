<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

final class Redactor
{
    public const string REDACTED = '[redacted]';

    private const string PAN_PATTERN = '/(?<!\d)\d(?:[ -]?\d){12,18}(?!\d)/';

    public static function maskPan(string $value): string
    {
        if (strlen($value) < 13) {
            return $value;
        }

        return preg_replace_callback(self::PAN_PATTERN, self::maskDigits(...), $value) ?? $value;
    }

    public static function forMessage(string $value): string
    {
        return addcslashes(self::maskPan($value), "\0..\37\177");
    }

    /** @param array{0: string} $match */
    private static function maskDigits(array $match): string
    {
        $pan = $match[0];
        $kept = 0;

        for ($i = strlen($pan) - 1; $i >= 0; $i--) {
            if ($pan[$i] !== ' ' && $pan[$i] !== '-' && $kept++ >= 4) {
                $pan[$i] = '*';
            }
        }

        return $pan;
    }
}
