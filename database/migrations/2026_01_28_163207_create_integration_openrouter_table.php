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
        Schema::create('integration_openrouter', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->text('api_key');
            $table->string('default_model')->default('anthropic/claude-3.5-sonnet');
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
        Schema::dropIfExists('integration_openrouter');
    }
};
