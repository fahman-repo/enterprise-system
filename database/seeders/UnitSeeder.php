<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Seed the standard units of measure.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Piece', 'abbreviation' => 'pcs', 'allows_decimal' => false],
            ['name' => 'Box', 'abbreviation' => 'box', 'allows_decimal' => false],
            ['name' => 'Pack', 'abbreviation' => 'pack', 'allows_decimal' => false],
            ['name' => 'Dozen', 'abbreviation' => 'dz', 'allows_decimal' => false],
            ['name' => 'Set', 'abbreviation' => 'set', 'allows_decimal' => false],
            ['name' => 'Kilogram', 'abbreviation' => 'kg', 'allows_decimal' => true],
            ['name' => 'Gram', 'abbreviation' => 'g', 'allows_decimal' => true],
            ['name' => 'Liter', 'abbreviation' => 'l', 'allows_decimal' => true],
            ['name' => 'Milliliter', 'abbreviation' => 'ml', 'allows_decimal' => true],
            ['name' => 'Meter', 'abbreviation' => 'm', 'allows_decimal' => true],
            ['name' => 'Square Meter', 'abbreviation' => 'm2', 'allows_decimal' => true],
            ['name' => 'Roll', 'abbreviation' => 'roll', 'allows_decimal' => true],
        ];

        foreach ($units as $attributes) {
            Unit::query()->updateOrCreate(
                ['abbreviation' => $attributes['abbreviation']],
                $attributes,
            );
        }
    }
}
