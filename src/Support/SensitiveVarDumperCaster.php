<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Support;

use StdOut\SimpleDataObjects\BaseData;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;

final class SensitiveVarDumperCaster
{
    public static function register(): void
    {
        AbstractCloner::addDefaultCasters([BaseData::class => [self::class, 'cast']]);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function cast(BaseData $data, array $properties): array
    {
        $sensitive = MetadataRegistry::get($data::class)->sensitiveFields;

        if ($sensitive === []) {
            return $properties;
        }

        foreach ($properties as $key => $value) {
            if ($value !== null && isset($sensitive[substr($key, (int) strrpos("\0".$key, "\0"))])) {
                $properties[$key] = Redactor::REDACTED;
            }
        }

        return $properties;
    }
}
