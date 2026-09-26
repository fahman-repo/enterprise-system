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
        Schema::create('benefits', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255)->unique();
            $table->string('type', 32)->index();
            $table->text('description')->nullable();
            $table->decimal('limit_amount', 15, 2)->default(0);
            $table->string('period', 16)->default('yearly')->index();
            $table->unsignedInteger('min_tenure_months')->default(0);
            $table->boolean('requires_receipt')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('benefit_eligible_grade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benefit_id')->constrained('benefits')->restrictOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['benefit_id', 'grade_id']);
        });

        Schema::create('benefit_eligible_employment_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benefit_id')->constrained('benefits')->restrictOnDelete();
            $table->foreignId('employment_status_id')->constrained('employment_statuses')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['benefit_id', 'employment_status_id']);
        });

        Schema::create('benefit_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benefit_id')->constrained('benefits')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['benefit_id', 'employee_id']);
        });

        Schema::create('benefit_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benefit_id')->constrained('benefits')->restrictOnDelete();
            $table->foreignId('benefit_enrollment_id')->constrained('benefit_enrollments')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->date('claim_date')->index();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('resolution_comment')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['benefit_enrollment_id', 'status']);
            $table->index(['status', 'claim_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benefit_claims');
        Schema::dropIfExists('benefit_enrollments');
        Schema::dropIfExists('benefit_eligible_employment_status');
        Schema::dropIfExists('benefit_eligible_grade');
        Schema::dropIfExists('benefits');
    }
};
