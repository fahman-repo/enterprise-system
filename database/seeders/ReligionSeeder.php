<?php

namespace Database\Seeders;

use App\Models\Religion;
use Illuminate\Database\Seeder;

class ReligionSeeder extends Seeder
{
    /**
     * Seed the religions recognized for employee records.
     */
    public function run(): void
    {
        $religions = [
            ['name' => 'Islam', 'sort_order' => 1],
            ['name' => 'Protestant', 'sort_order' => 2],
            ['name' => 'Catholic', 'sort_order' => 3],
            ['name' => 'Hindu', 'sort_order' => 4],
            ['name' => 'Buddhist', 'sort_order' => 5],
            ['name' => 'Confucian', 'sort_order' => 6],
            ['name' => 'Other', 'sort_order' => 7],
        ];

        foreach ($religions as $attributes) {
            Religion::query()->updateOrCreate(
                ['name' => $attributes['name']],
                $attributes,
            );
        }
    }
}
