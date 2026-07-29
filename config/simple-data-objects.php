<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------
    | Metadata cache path
    |--------------------------------------------------------------------
    |
    | When set, StdOut\SimpleDataObjects\Support\MetadataRegistry persists
    | compiled hydrator/serializer closures to this directory, and `sdo:warm`
    | writes here by default. Leave null to keep the in-memory-only cache
    | (fine for most apps; see docs/laravel/service-provider.md).
    |
    */
    'cache_path' => null,

    /*
    |--------------------------------------------------------------------
    | Data object source paths
    |--------------------------------------------------------------------
    |
    | Scanned by `sdo:warm` when no paths are given on the command line, and
    | used as the default output directory for `make:data`.
    |
    */
    'paths' => [
        app_path('Data'),
    ],

    /*
    |--------------------------------------------------------------------
    | Controller injection
    |--------------------------------------------------------------------
    |
    | When true, type-hinting a BaseData subclass that uses
    | HasLaravelIntegration as a controller method parameter auto-hydrates
    | it from the current request via fromRequest() — no FormRequest
    | needed. Set to false to disable this container-level binding.
    |
    */
    'inject_from_request' => true,

];
