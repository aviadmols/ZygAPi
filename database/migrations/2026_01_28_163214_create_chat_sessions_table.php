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
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('automation_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('run_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->timestamps();
            
            $table->index(['shop_id', 'automation_id']);
            $table->index('run_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_sessions');
    }
};
