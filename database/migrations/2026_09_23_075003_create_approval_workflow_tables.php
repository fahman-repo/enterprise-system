<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_matrices', function (Blueprint $table) {
            $table->id();
            $table->string('module_key', 100)->unique();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('configuration_version')->default(1);
            $table->timestamps();
        });

        Schema::create('approval_matrix_maker_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_matrix_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['approval_matrix_id', 'role_id']);
        });

        Schema::create('approval_matrix_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_matrix_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('stage_number');
            $table->string('name', 100);
            $table->timestamps();

            $table->unique(['approval_matrix_id', 'stage_number']);
        });

        Schema::create('approval_matrix_stage_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_matrix_stage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['approval_matrix_stage_id', 'role_id']);
        });

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('module_key', 100);
            $table->string('action', 20);
            $table->unsignedBigInteger('target_id')->nullable()->index();
            $table->json('proposed_payload');
            $table->json('before_payload')->nullable();
            $table->foreignId('maker_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('maker_snapshot');
            $table->string('status', 20)->default('Pending');
            $table->unsignedSmallInteger('current_stage')->default(1);
            $table->unsignedInteger('matrix_configuration_version');
            $table->text('resolution_comment')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['module_key', 'status']);
            $table->unique(['module_key', 'action', 'target_id', 'status'], 'approval_requests_dedupe_unique');
        });

        Schema::create('approval_request_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('stage_number');
            $table->string('name', 100);
            $table->string('status', 20)->default('Pending');
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('decided_by_snapshot')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['approval_request_id', 'stage_number']);
            $table->index(['approval_request_id', 'status']);
        });

        Schema::create('approval_request_stage_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_stage_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('role_id')->index();
            $table->string('role_name');
            $table->string('role_slug');
            $table->timestamps();

            $table->unique(['approval_request_stage_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_request_stage_roles');
        Schema::dropIfExists('approval_request_stages');
        Schema::dropIfExists('approval_requests');
        Schema::dropIfExists('approval_matrix_stage_roles');
        Schema::dropIfExists('approval_matrix_stages');
        Schema::dropIfExists('approval_matrix_maker_roles');
        Schema::dropIfExists('approval_matrices');
    }
};
