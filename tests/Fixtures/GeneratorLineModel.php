<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class GeneratorLineModel extends Model
{
    public $timestamps = false;

    protected $table = 'generator_lines';

    protected $guarded = [];
}
