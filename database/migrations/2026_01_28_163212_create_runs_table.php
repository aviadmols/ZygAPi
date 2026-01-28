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
        Schema::create('runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('automation_id')->constrained()->onDelete('cascade');
            $table->string('mode'); // dry_run or execute
            $table->string('status')->default('queued');
            $table->string('trigger_type');
            $table->json('trigger_payload')->nullable();
            $table->string('external_order_id')->nullable();
            $table->string('order_number')->nullable();
            $table->string('external_subscription_id')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->json('execution_snapshot_json')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            
            $table->index(['shop_id', 'automation_id']);
            $table->index('idempotency_key');
            $table->index('external_order_id');
            $table->index('external_subscription_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('runs');
    }
};
