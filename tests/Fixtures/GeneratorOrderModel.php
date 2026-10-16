<?php

declare(strict_types=1);

namespace StdOut\SimpleDataObjects\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeneratorOrderModel extends Model
{
    public $timestamps = false;

    protected $table = 'generator_orders';

    protected $guarded = [];

    protected $hidden = ['secret'];

    protected $casts = ['paid' => 'boolean', 'total' => 'float'];

    /** @return HasMany<GeneratorLineModel, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(GeneratorLineModel::class, 'order_id');
    }
}
