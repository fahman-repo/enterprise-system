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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('employee_number')->unique();
            $table->string('name');
            $table->string('gender', 16);
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->foreignId('religion_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('marital_status_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('education_level_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->string('identity_number', 50)->nullable()->unique();
            $table->string('npwp', 50)->nullable();
            $table->string('bpjs_kesehatan', 50)->nullable();
            $table->string('bpjs_ketenagakerjaan', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employment_status_id')->nullable()->constrained()->nullOnDelete();
            $table->date('join_date');
            $table->date('end_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('is_active');
            $table->index('join_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
