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
        Schema::create('api_knowledge_items', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // shopify, recharge, internal
            $table->string('title');
            $table->text('content');
            $table->json('tags')->nullable();
            $table->json('json_examples')->nullable();
            $table->timestamps();
            
            $table->index('provider');
            $table->index('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_knowledge_items');
    }
};
