<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->string('approval_mode', 20)->default('sequential')->after('matrix_configuration_version');
        });

        DB::table('approval_requests')->whereNull('approval_mode')->update(['approval_mode' => 'sequential']);
    }

    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropColumn('approval_mode');
        });
    }
};
