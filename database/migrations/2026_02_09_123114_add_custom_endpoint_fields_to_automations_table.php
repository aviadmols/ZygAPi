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
        Schema::table('automations', function (Blueprint $table) {
            $table->string('custom_endpoint_url')->nullable()->after('trigger_config');
            $table->text('custom_endpoint_prompt')->nullable()->after('custom_endpoint_url');
            $table->json('custom_endpoint_platforms')->nullable()->after('custom_endpoint_prompt');
            $table->text('custom_endpoint_generated_code')->nullable()->after('custom_endpoint_platforms');
            $table->json('custom_endpoint_input_schema')->nullable()->after('custom_endpoint_generated_code');
            $table->json('custom_endpoint_test_results')->nullable()->after('custom_endpoint_input_schema');
            
            $table->index('custom_endpoint_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automations', function (Blueprint $table) {
            $table->dropIndex(['custom_endpoint_url']);
            $table->dropColumn([
                'custom_endpoint_url',
                'custom_endpoint_prompt',
                'custom_endpoint_platforms',
                'custom_endpoint_generated_code',
                'custom_endpoint_input_schema',
                'custom_endpoint_test_results',
            ]);
        });
    }
};
