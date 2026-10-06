---
layout: home

hero:
  name: Simple Data Objects
  text: Typed PHP data objects, compiled for speed
  tagline: Up to 60× faster than the most popular alternative, with the same attribute-driven classes. Hydration, validation, casting and serialization — no boilerplate, no runtime reflection. PHP 8.4+, plain PHP or Laravel 12–13.
  actions:
    - theme: brand
      text: Quick start
      link: /guide/quick-start
    - theme: alt
      text: See the benchmarks
      link: /guide/performance
    - theme: alt
      text: GitHub
      link: https://github.com/std-out/simple-data-objects

features:
  - title: Turn input into objects
    details: Arrays, JSON, requests, Eloquent models — one from() call gives you a typed, immutable object.
    link: /features/hydration
    linkText: Hydration
  - title: Validate and report errors
    details: Laravel validation rules next to the property, or inferred from its type. Throw, or collect every error at once.
    link: /features/validation
    linkText: Validation
  - title: Convert types
    details: Dates, enums, money, JSON, encrypted values — built-in casts, or your own in a dozen lines.
    link: /casts/
    linkText: Built-in casts
  - title: Send data back out
    details: toArray() and toJson() with renamed keys, hidden fields, computed values and per-context visibility.
    link: /features/serialization
    linkText: Serialization
  - title: Import large files
    details: Stream a CSV or a 50 MB XML feed one record at a time. Memory stays flat however big the file is.
    link: /features/xml
    linkText: Streaming XML
  - title: Use it with Laravel
    details: Requests, controller injection, Eloquent casts, Livewire and pagination — each one opt-in.
    link: /integrations/laravel
    linkText: Laravel setup
---

<div class="sdo-home">

<div class="sdo-section">

## The whole idea in one screen

<p class="sdo-lead">A data object is a class with typed constructor properties. Attributes describe anything that isn't obvious from the types.</p>

<div class="sdo-duo">
<div>

Define it once

```php
use StdOut\SimpleDataObjects\BaseData;
use StdOut\SimpleDataObjects\Attributes\{Cast, Rules};
use StdOut\SimpleDataObjects\Casts\DateTimeCast;

class UserData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'string', 'max:120'])]
        public readonly string $name,

        #[Rules(['required', 'email'])]
        public readonly string $email,

        #[Cast(new DateTimeCast('Y-m-d'))]
        public readonly ?DateTime $birthday = null,
    ) {}
}
```

</div>
<div>

Use it everywhere

```php
// from an array, JSON, a request, a model…
$user = UserData::from($request->all());

// …validated first, if you want
$user = UserData::fromValidated($payload);

$user->name;                  // typed, autocompleted
$user->birthday?->format('d M');

$user->toArray();             // back to plain data
$user->with(name: 'Alice');   // a modified copy

UserData::collection($rows);  // typed collection
```

</div>
</div>

</div>

<div class="sdo-section">

## Why it is fast

<p class="sdo-lead">Speed is the reason this library exists, not a side effect. Three decisions account for it.</p>

<div class="sdo-stats">

- [<strong>Compiled</strong><span>Each class is turned once into a specialised hydrator and serializer — plain array reads and a constructor call, with no per-field dispatch.</span>](/features/cache)
- [<strong>Zero reflection</strong><span>Metadata and compiled code are written to an opcache-friendly file cache at deploy time. Nothing is discovered at runtime.</span>](/features/cache)
- [<strong>Streaming</strong><span>Large CSV and XML imports are read one record at a time: 12× less process memory than a SimpleXML loop on a 52 MB feed.</span>](/features/xml)

</div>

</div>

<div class="sdo-section">

## Where to go next

<div class="sdo-links">

- [Installation<small>Composer, requirements</small>](/guide/installation)
- [Attributes<small>Every attribute on one page</small>](/attributes/)
- [Plain PHP<small>No framework needed</small>](/integrations/plain-php)
- [Migrating<small>From spatie/laravel-data</small>](/guide/migrating-from-laravel-data)

</div>

</div>

</div>
