<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('work_locations')) {
            return;
        }

        $locations = DB::table('work_locations')
            ->orderBy('id')
            ->get([
                'id',
                'code',
                'name',
                'address',
                'city',
                'province',
                'postal_code',
                'phone',
                'is_active',
            ]);

        foreach ($locations as $location) {
            DB::table('sites')->insert([
                'parent_id' => null,
                'code' => $location->code,
                'name' => $location->name,
                'type' => $this->typeFor($location->code),
                'description' => null,
                'address' => $location->address,
                'city' => $location->city,
                'province' => $location->province,
                'postal_code' => $location->postal_code,
                'phone' => $location->phone,
                'email' => null,
                'notes' => null,
                'is_active' => $location->is_active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Employee backfills run in the follow-up migration, once the
        // employees.site_id column actually exists.
    }

    /**
     * Map a legacy work location code onto a site type so the migrated
     * rows start life categorized: HQ as a company, plants as factories,
     * warehouses and branches as such, everything else as a branch.
     */
    protected function typeFor(string $code): string
    {
        return match (true) {
            str_starts_with($code, 'HQ-') => 'company',
            str_starts_with($code, 'PL-') => 'factory',
            str_starts_with($code, 'WH-') => 'warehouse',
            default => 'branch',
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Every work location row is restored by the follow-up
        // "replace_work_location_id_with_site_id_on_employees" migration
        // and the sites table is dropped with its own migration, so this
        // step has nothing to undo.
    }
};
