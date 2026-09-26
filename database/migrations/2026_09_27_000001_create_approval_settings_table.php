<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 20)->default('sequential');
            $table->timestamps();
        });

        DB::table('approval_settings')->updateOrInsert(
            ['id' => 1],
            ['mode' => 'sequential', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_settings');
    }
};
