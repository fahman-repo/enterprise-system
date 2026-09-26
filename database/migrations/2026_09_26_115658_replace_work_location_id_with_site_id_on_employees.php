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
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('grade_id')->constrained()->nullOnDelete();
        });

        if (! Schema::hasTable('work_locations')) {
            return;
        }

        $siteIdByCode = DB::table('sites')->pluck('id', 'code');

        DB::table('work_locations')
            ->orderBy('id')
            ->each(function (object $location) use ($siteIdByCode): void {
                $siteId = $siteIdByCode[$location->code] ?? null;

                if ($siteId === null) {
                    return;
                }

                DB::table('employees')
                    ->where('work_location_id', $location->id)
                    ->update(['site_id' => $siteId]);
            });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('work_location_id')->nullable()->after('grade_id')->constrained()->nullOnDelete();
        });

        if (Schema::hasTable('work_locations')) {
            $locationIdByCode = DB::table('work_locations')->pluck('id', 'code');

            DB::table('sites')
                ->orderBy('id')
                ->each(function (object $site) use ($locationIdByCode): void {
                    $locationId = $locationIdByCode[$site->code] ?? null;

                    if ($locationId === null) {
                        return;
                    }

                    DB::table('employees')
                        ->where('site_id', $site->id)
                        ->update(['work_location_id' => $locationId]);
                });
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_id');
        });
    }
};
