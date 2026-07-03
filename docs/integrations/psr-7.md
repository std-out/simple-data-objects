# Slim, Mezzio & any PSR-7 framework

For frameworks built on PSR-7 (Slim, Mezzio/Laminas, Chubbyphp, custom middlewares) the integration is a single line at the edge: PSR-7 gives you arrays, and `from()` takes arrays.

## Setup

```php
// bootstrap
use StdOut\SimpleDataObjects\Support\MetadataRegistry;

MetadataRegistry::setStoragePath(__DIR__.'/../var/cache/data-objects');
```

## Request handling

```php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$app->post('/orders', function (Request $request, Response $response) {
    $order = OrderData::fromValidated((array) $request->getParsedBody());

    $response->getBody()->write($order->toJson());

    return $response->withHeader('Content-Type', 'application/json');
});
```

Query parameters work the same way:

```php
$filters = SearchFilterData::from($request->getQueryParams());
```

For a raw JSON body without a body-parsing middleware, pass the string straight in — `from()` decodes it:

```php
$order = OrderData::from((string) $request->getBody());
```

## Validation

`#[Rules]` validation runs standalone (see [Validation](../features/validation.md)) — `fromValidated()` throws `Illuminate\Validation\ValidationException`, which your error middleware can map to a 422 response:

```php
$app->addErrorMiddleware(...)->getDefaultErrorHandler();
// map ValidationException::errors() to your problem-details format
```

## Deploy

```sh
vendor/bin/sdo-warm var/cache/data-objects src/Data
```
