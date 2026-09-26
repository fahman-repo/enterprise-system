<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('development_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('development_program_id')->constrained('development_programs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('status', 32)->default('registered');
            $table->decimal('score', 5, 2)->nullable();
            $table->date('completed_at')->nullable();
            $table->string('certificate_no', 100)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['development_program_id', 'employee_id']);
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('development_enrollments');
    }
};
