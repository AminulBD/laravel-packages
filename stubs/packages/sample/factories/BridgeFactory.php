<?php

namespace YourDomain\Sample\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use YourDomain\Sample\Models\Sample;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\YourDomain\Sample\Models\Sample>
 */
class SampleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\TModel>
     */
    protected $model = Sample::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sample' => fake()->streetName(),
        ];
    }
}
