<?php

namespace Database\Seeders;

use App\Models\MaritalStatus;
use Illuminate\Database\Seeder;

class MaritalStatusSeeder extends Seeder
{
    /**
     * Seed the marital statuses.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'Single', 'sort_order' => 1],
            ['name' => 'Married', 'sort_order' => 2],
            ['name' => 'Divorced', 'sort_order' => 3],
            ['name' => 'Widowed', 'sort_order' => 4],
        ];

        foreach ($statuses as $attributes) {
            MaritalStatus::query()->updateOrCreate(
                ['name' => $attributes['name']],
                $attributes,
            );
        }
    }
}
