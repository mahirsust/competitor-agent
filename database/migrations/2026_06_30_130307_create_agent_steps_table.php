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
        Schema::create('agent_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agent_run_id');
            $table->integer('step_number');
            $table->enum('type', ['thought', 'tool_call', 'observation', 'finish']);
            $table->string('tool_name')->nullable();
            $table->json('tool_input')->nullable();
            $table->longText('content');
            $table->timestamps();

            $table->foreign('agent_run_id')->references('id')->on('agent_runs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_steps');
    }
};
