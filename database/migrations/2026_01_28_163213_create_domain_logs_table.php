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
        Schema::create('domain_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->string('object_type');
            $table->string('object_external_id')->nullable();
            $table->string('object_display')->nullable();
            $table->string('action_type');
            $table->foreignId('run_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('run_step_id')->nullable()->constrained('run_steps')->onDelete('set null');
            $table->string('status');
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index(['shop_id', 'object_type', 'object_external_id']);
            $table->index('run_id');
            $table->index('action_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_logs');
    }
};
