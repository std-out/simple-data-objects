# JsonCast

Decodes JSON strings to PHP arrays on hydration; encodes PHP arrays to JSON strings on serialization.

## Usage

```php
use StdOut\SimpleDataObjects\Attributes\Cast;
use StdOut\SimpleDataObjects\Casts\JsonCast;

class ConfigData extends BaseData
{
    public function __construct(
        public readonly string $name,

        #[Cast(new JsonCast)]
        public readonly array $settings,
    ) {}
}
```

### Hydration

```php
$config = ConfigData::from([
    'name'     => 'My App',
    'settings' => '{"theme":"dark","language":"uk"}',
]);

$config->settings; // ['theme' => 'dark', 'language' => 'uk']
```

### Serialization

```php
$config->toArray()['settings']; // '{"theme":"dark","language":"uk"}'
```

## Depth Limit

JSON decoding is limited to **64 levels** of nesting to prevent stack exhaustion on adversarial input.

## Null Handling

Returns `null` for `null` input on both hydration and serialization.

## Error Handling

Throws `InvalidArgumentException` on malformed JSON:

```php
ConfigData::from(['name' => 'App', 'settings' => '{bad json}']);
// throws InvalidArgumentException: Failed to decode JSON: Syntax error
```
