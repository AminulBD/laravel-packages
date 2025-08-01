<?php

namespace YourDomain\Sample\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use YourDomain\Sample\Factories\SampleFactory;

/**
 * @template TFactory of \Illuminate\Database\Eloquent\Factories\Factory
 */
class Sample extends Model
{
    use HasFactory;

    /**
     * The name of the model's corresponding factory.
     *
     * @var class-string<TFactory>
     */
    protected static $factory = SampleFactory::class;
}
