<?php

namespace Database\Seeders;

use App\Models\EmploymentStatus;
use Illuminate\Database\Seeder;

class EmploymentStatusSeeder extends Seeder
{
    /**
     * Seed the employment statuses.
     */
    public function run(): void
    {
        $statuses = [
            ['code' => 'PERM', 'name' => 'Permanent', 'sort_order' => 1, 'description' => 'Open-ended employment contract.'],
            ['code' => 'CONT', 'name' => 'Contract', 'sort_order' => 2, 'description' => 'Fixed-term employment contract.'],
            ['code' => 'PROB', 'name' => 'Probation', 'sort_order' => 3, 'description' => 'Trial period before permanent employment.'],
            ['code' => 'INT', 'name' => 'Internship', 'sort_order' => 4, 'description' => 'Student or fresh graduate internship.'],
            ['code' => 'OUT', 'name' => 'Outsourced', 'sort_order' => 5, 'description' => 'Third-party contracted workforce.'],
        ];

        foreach ($statuses as $attributes) {
            EmploymentStatus::query()->updateOrCreate(
                ['code' => $attributes['code']],
                $attributes,
            );
        }
    }
}
