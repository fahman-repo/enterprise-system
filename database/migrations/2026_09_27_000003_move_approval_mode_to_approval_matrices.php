<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_matrices', function (Blueprint $table) {
            $table->string('mode', 20)->default('sequential')->after('configuration_version');
        });

        $mode = Schema::hasTable('approval_settings')
            ? DB::table('approval_settings')->where('id', 1)->value('mode')
            : null;

        DB::table('approval_matrices')->update(['mode' => $mode ?? 'sequential']);

        Schema::dropIfExists('approval_settings');
    }

    public function down(): void
    {
        Schema::create('approval_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 20)->default('sequential');
            $table->timestamps();
        });

        DB::table('approval_settings')->insert([
            'id' => 1,
            'mode' => 'sequential',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('approval_matrices', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};
