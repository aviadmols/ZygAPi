<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tagging_rules', function (Blueprint $table) {
            $table->string('webhook_token', 64)->nullable()->after('overwrite_existing_tags');
        });

        Schema::create('tagging_rule_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagging_rule_id')->constrained()->onDelete('cascade');
            $table->string('order_id', 64);
            $table->string('order_number', 32)->nullable();
            $table->json('tags_applied')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->string('source', 32)->nullable(); // 'webhook' | 'dashboard' | 'api'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagging_rule_logs');
        Schema::table('tagging_rules', function (Blueprint $table) {
            $table->dropColumn('webhook_token');
        });
    }
};
