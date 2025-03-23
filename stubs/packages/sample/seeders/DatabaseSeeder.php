<?php

namespace YourDomain\Sample\Seeders;

use Illuminate\Database\Seeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use YourDomain\Sample\Models\Sample;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Sample::factory()->create([
            'sample' => 'Something new!'
        ]);
    }
}
