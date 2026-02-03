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
        Schema::create('order_processing_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('rule_id')->nullable()->constrained('tagging_rules')->onDelete('set null');
            $table->json('order_ids'); // List of order IDs for processing
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->json('progress')->nullable(); // Detailed progress information
            $table->integer('total_orders')->default(0);
            $table->integer('processed_orders')->default(0);
            $table->integer('failed_orders')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_processing_jobs');
    }
};
