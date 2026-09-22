<?php

namespace Database\Seeders;

use App\Models\Entity;
use Illuminate\Database\Seeder;

class EntitySeeder extends Seeder
{
    /**
     * Seed a demo set of vendor and customer entities.
     */
    public function run(): void
    {
        if (Entity::query()->withTrashed()->exists()) {
            return;
        }

        for ($index = 1; $index <= 20; $index++) {
            $factory = Entity::factory()->state([
                'code' => 'ENT-'.str_pad((string) $index, 5, '0', STR_PAD_LEFT),
            ]);

            $factory = match ($index % 3) {
                1 => $factory->vendor(),
                2 => $factory->customer(),
                default => $factory->vendorAndCustomer(),
            };

            $factory = $index % 2 === 1 ? $factory->personal() : $factory->company();

            if ($index % 7 === 0) {
                $factory = $factory->inactive();
            }

            $factory->create();
        }
    }
}
