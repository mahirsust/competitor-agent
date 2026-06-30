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
        Schema::create('report_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agent_run_id');
            $table->string('competitor_name');
            $table->string('section');
            $table->longText('content');
            $table->boolean('is_grounded')->default(true);
            $table->timestamps();

            $table->foreign('agent_run_id')->references('id')->on('agent_runs');
            $table->unique(['agent_run_id', 'competitor_name', 'section']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_sections');
    }
};
