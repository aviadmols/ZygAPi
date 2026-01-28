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
        Schema::create('run_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained()->onDelete('cascade');
            $table->string('step_id');
            $table->string('step_name');
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->json('error')->nullable();
            $table->json('http_request')->nullable();
            $table->json('http_response')->nullable();
            $table->json('simulation_diff')->nullable();
            $table->timestamps();
            
            $table->index('run_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('run_steps');
    }
};
