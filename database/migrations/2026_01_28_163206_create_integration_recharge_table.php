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
        Schema::create('integration_recharge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->string('base_url')->nullable();
            $table->text('access_token');
            $table->text('webhook_secret')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
            
            $table->unique('shop_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_recharge');
    }
};
