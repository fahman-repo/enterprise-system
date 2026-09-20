<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Seed the company divisions.
     */
    public function run(): void
    {
        $divisions = [
            ['code' => 'DIV-HR', 'name' => 'Human Resources', 'description' => 'People management, recruitment and employee administration.'],
            ['code' => 'DIV-FIN', 'name' => 'Finance & Accounting', 'description' => 'Financial planning, accounting and reporting.'],
            ['code' => 'DIV-OPS', 'name' => 'Operations', 'description' => 'Production and day-to-day operational activities.'],
            ['code' => 'DIV-COM', 'name' => 'Commercial', 'description' => 'Sales and marketing.'],
            ['code' => 'DIV-IT', 'name' => 'Information Technology', 'description' => 'Technology infrastructure and software delivery.'],
        ];

        foreach ($divisions as $attributes) {
            Division::query()->updateOrCreate(
                ['code' => $attributes['code']],
                $attributes,
            );
        }
    }
}
