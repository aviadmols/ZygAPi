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
        Schema::create('tagging_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('rules_json')->nullable(); // חוקיות שנוצרו ע"י AI
            $table->text('tags_template')->nullable(); // תבנית תגיות עם פונקציות
            $table->boolean('is_active')->default(true);
            $table->boolean('overwrite_existing_tags')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagging_rules');
    }
};
