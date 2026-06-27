# Validation

Validation is driven by `#[Rules([...])]` attributes on constructor parameters. Under the hood it uses Laravel's `Illuminate\Validation\Factory`, which means the full power of Laravel's rule system is available — including `Rule` objects, closures, and `after` hooks.

## Declaring Rules

```php
use StdOut\SimpleDataObjects\Attributes\Rules;
use Illuminate\Validation\Rules\Password;

class RegisterData extends BaseData
{
    public function __construct(
        #[Rules(['required', 'string', 'max:100'])]
        public readonly string $name,

        #[Rules(['required', 'email:rfc,dns'])]
        public readonly string $email,

        #[Rules(['required', new Password->min(8)->letters()->numbers()->symbols()])]
        public readonly string $password,

        #[Rules(['nullable', 'string', 'min:3', 'max:20'])]
        public readonly ?string $username = null,
    ) {}
}
```

## fromValidated() — Validate then Hydrate

Throws `Illuminate\Validation\ValidationException` if validation fails; returns a hydrated instance on success.

```php
use Illuminate\Validation\ValidationException;

try {
    $data = RegisterData::fromValidated($request->all());
} catch (ValidationException $e) {
    $errors = $e->errors();
    // ['email' => ['The email field must be a valid email address.']]
}
```

## validate() — Only Validate

Validate without hydrating. Useful to check data at a boundary before passing it further.

```php
RegisterData::validate($request->all()); // throws or returns void
```

## from() — Skip Validation

`from()` never validates. Use it for trusted internal data (seeds, tests, internal transformations):

```php
$data = RegisterData::from($trustedArray); // no validation
```

## Validation Errors

All fields are validated at once. The `ValidationException` contains all field errors:

```php
try {
    RegisterData::fromValidated([]);
} catch (ValidationException $e) {
    $e->errors();
    // [
    //   'name'     => ['The name field is required.'],
    //   'email'    => ['The email field is required.'],
    //   'password' => ['The password field is required.'],
    // ]
}
```

## Standalone Usage (without Laravel)

When no Laravel container is present, a minimal `Illuminate\Validation\Factory` is bootstrapped automatically with English messages:

```php
// Works in any PHP 8.4+ project
RegisterData::validate(['email' => 'bad']); // throws ValidationException
```

## Custom Validator Factory

Inject your own factory for custom translation, custom rules, or testing:

```php
use Illuminate\Validation\Factory;

BaseData::setValidatorFactory($myFactory);
```

## Laravel: fromRequest() Auto-validates

When using `HasLaravelIntegration`, `fromRequest()` calls `fromValidated()` automatically:

```php
class CreateUserController
{
    public function __invoke(Request $request): JsonResponse
    {
        // Validates #[Rules] + hydrates, or throws ValidationException
        $data = UserData::fromRequest($request);

        return $data->toResponse($request);
    }
}
```
