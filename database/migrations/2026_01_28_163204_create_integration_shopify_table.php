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
        Schema::create('integration_shopify', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->string('shop_domain');
            $table->text('access_token');
            $table->string('api_version')->default('2024-01');
            $table->text('webhook_secret')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
            
            $table->unique('shop_id');
            $table->index('shop_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_shopify');
    }
};
