<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_endpoint_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_endpoint_id')->constrained()->onDelete('cascade');
            $table->json('request_input')->nullable();
            $table->json('response_data')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_endpoint_logs');
    }
};
