<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Concerns;

use StdOut\SimpleDataObjects\Support\MetadataRegistry;
use StdOut\SimpleDataObjects\Support\Redactor;

trait HasSensitiveFields
{
    public function __serialize(): array
    {
        (new \ReflectionClass($this))->initializeLazyObject($this);

        $vars = get_mangled_object_vars($this);
        $exposed = self::sensitiveKeys($vars);

        if ($exposed !== []) {
            throw new \LogicException(sprintf('Cannot serialize %s: field \'%s\' is #[Sensitive].', static::class, reset($exposed)));
        }

        return $vars;
    }

    public function __debugInfo(): array
    {
        if ((new \ReflectionClass($this))->isUninitializedLazyObject($this)) {
            return [];
        }

        $vars = get_mangled_object_vars($this);

        foreach (array_keys(self::sensitiveKeys($vars)) as $key) {
            $vars[$key] = Redactor::REDACTED;
        }

        return $vars;
    }

    /**
     * @param  array<string, mixed>  $vars
     * @return array<string, string>
     */
    private static function sensitiveKeys(array $vars): array
    {
        $sensitive = MetadataRegistry::get(static::class)->sensitiveFields;
        $keys = [];

        foreach ($vars as $key => $value) {
            $name = substr($key, (int) strrpos("\0".$key, "\0"));

            if ($value !== null && isset($sensitive[$name])) {
                $keys[$key] = $name;
            }
        }

        return $keys;
    }
}
