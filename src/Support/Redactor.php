<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

final class Redactor
{
    public const string REDACTED = '[redacted]';

    private const string PAN_PATTERN = '/(?<!\d)\d(?:[ -]?\d){12,18}(?!\d)/';

    public static function maskPan(#[\SensitiveParameter] string $value): string
    {
        if (strlen($value) < 13) {
            return $value;
        }

        return preg_replace_callback(self::PAN_PATTERN, self::maskDigits(...), $value) ?? $value;
    }

    public static function forMessage(#[\SensitiveParameter] string $value): string
    {
        return addcslashes(self::maskPan($value), "\0..\37\177");
    }

    /**
     * The library passes input to a data class constructor it does not own,
     * so that frame cannot carry #[\SensitiveParameter]: a TypeError at the
     * call or any exception thrown in the constructor body would keep the raw
     * arguments in its trace. Wraps them the way PHP wraps a sensitive
     * parameter, in place, so the type, message and line stay the same.
     *
     * @param  class-string  $class
     */
    public static function scrubConstructorArguments(\Throwable $e, string $class): \Throwable
    {
        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            $property = new \ReflectionProperty($current instanceof \Exception ? \Exception::class : \Error::class, 'trace');
            /** @var list<array<string, mixed>> $trace */
            $trace = $property->getValue($current);

            foreach ($trace as $i => $frame) {
                if (($frame['function'] ?? null) === '__construct'
                    && isset($frame['class'], $frame['args'])
                    && is_a($class, (string) $frame['class'], true)) {
                    $trace[$i]['args'] = array_map(
                        static fn (mixed $arg): \SensitiveParameterValue => $arg instanceof \SensitiveParameterValue ? $arg : new \SensitiveParameterValue($arg),
                        (array) $frame['args'],
                    );
                }
            }

            $property->setValue($current, $trace);
        }

        return $e;
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
