<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Seed the job grades.
     */
    public function run(): void
    {
        $grades = [
            ['name' => 'Staff', 'level' => 1, 'description' => 'Individual contributor.'],
            ['name' => 'Senior Staff', 'level' => 2, 'description' => 'Experienced individual contributor.'],
            ['name' => 'Supervisor', 'level' => 3, 'description' => 'First-line team supervision.'],
            ['name' => 'Assistant Manager', 'level' => 4, 'description' => 'Assists a department manager.'],
            ['name' => 'Manager', 'level' => 5, 'description' => 'Owns a department.'],
            ['name' => 'General Manager', 'level' => 6, 'description' => 'Owns a division.'],
            ['name' => 'Director', 'level' => 7, 'description' => 'Executive leadership.'],
        ];

        foreach ($grades as $attributes) {
            Grade::query()->updateOrCreate(
                ['name' => $attributes['name']],
                $attributes,
            );
        }
    }
}
