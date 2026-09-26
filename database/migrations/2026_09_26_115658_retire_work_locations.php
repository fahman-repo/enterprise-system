<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The approvals module registry is keyed by module_key, so any
        // leftover work-locations requests would break the approvals
        // screens once the module is unregistered. Its matrix, the sidebar
        // entry and the table itself go with it.
        DB::table('approval_requests')->where('module_key', 'work-locations')->delete();
        DB::table('approval_matrices')->where('module_key', 'work-locations')->delete();
        DB::table('menus')->where('slug', 'work-locations')->delete();

        Schema::dropIfExists('work_locations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('work_locations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('name');
            $table->index('is_active');
        });

        // Employee links are restored by the previous migration's rollback,
        // which re-adds employees.work_location_id and maps it by code.
        DB::table('sites')
            ->orderBy('id')
            ->each(function (object $site): void {
                DB::table('work_locations')->insertOrIgnore([
                    'code' => $site->code,
                    'name' => $site->name,
                    'address' => $site->address,
                    'city' => $site->city,
                    'province' => $site->province,
                    'postal_code' => $site->postal_code,
                    'phone' => $site->phone,
                    'is_active' => $site->is_active,
                    'created_at' => $site->created_at,
                    'updated_at' => $site->updated_at,
                ]);
            });
    }
};
