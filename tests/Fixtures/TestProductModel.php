<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class TestProductModel extends Model
{
    public $timestamps = false;

    protected $table = 'test_products';

    protected $guarded = [];
}
