<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_endpoints', function (Blueprint $table) {
            $table->foreignId('store_id')->after('id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('platform', 32); // 'shopify' | 'recharge'
            $table->text('prompt');
            $table->json('input_params')->nullable(); // [{"name":"order_id","description":"..."}]
            $table->json('test_return_values')->nullable(); // [{"name":"tags","value":"tag1,tag2"}]
            $table->longText('php_code')->nullable();
            $table->string('webhook_token', 64)->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('custom_endpoints', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn([
                'name', 'slug', 'description', 'platform', 'prompt',
                'input_params', 'test_return_values', 'php_code', 'webhook_token', 'is_active'
            ]);
        });
    }
};
