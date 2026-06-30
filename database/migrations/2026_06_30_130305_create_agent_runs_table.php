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
        Schema::create('agent_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained();
            $table->text('goal');
            $table->enum('status', [
                'pending', 'running', 'pending_review', 'done', 'aborted', 'rejected',
            ])->default('pending');
            $table->integer('step_count')->default(0);
            $table->integer('max_steps')->default(30);
            $table->integer('estimated_cost_cents')->default(0);
            $table->integer('max_cost_cents')->default(50);
            $table->text('final_report')->nullable();
            $table->string('pdf_path')->nullable();
            $table->boolean('has_ungrounded_sections')->default(false);
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_runs');
    }
};
