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
        Schema::table('tagging_rules', function (Blueprint $table) {
            $table->longText('php_rule')->nullable()->after('tags_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tagging_rules', function (Blueprint $table) {
            $table->dropColumn('php_rule');
        });
    }
};
