<?php

namespace Database\Seeders;

use App\Models\EducationLevel;
use Illuminate\Database\Seeder;

class EducationLevelSeeder extends Seeder
{
    /**
     * Seed the formal education levels.
     */
    public function run(): void
    {
        $levels = [
            ['name' => 'Elementary School', 'level' => 1, 'sort_order' => 1],
            ['name' => 'Junior High School', 'level' => 2, 'sort_order' => 2],
            ['name' => 'Senior High School', 'level' => 3, 'sort_order' => 3],
            ['name' => 'Diploma', 'level' => 4, 'sort_order' => 4],
            ['name' => 'Bachelor', 'level' => 5, 'sort_order' => 5],
            ['name' => 'Master', 'level' => 6, 'sort_order' => 6],
            ['name' => 'Doctorate', 'level' => 7, 'sort_order' => 7],
        ];

        foreach ($levels as $attributes) {
            EducationLevel::query()->updateOrCreate(
                ['name' => $attributes['name']],
                $attributes,
            );
        }
    }
}
