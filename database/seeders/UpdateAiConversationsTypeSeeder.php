<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateAiConversationsTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Check if type column exists
            $schema = \Illuminate\Support\Facades\Schema::getConnection()->getSchemaBuilder();
            if ($schema->hasColumn('ai_conversations', 'type')) {
                // Update existing records that have NULL type to 'tags'
                \DB::table('ai_conversations')->whereNull('type')->update(['type' => 'tags']);
            }
        } catch (\Throwable $e) {
            // Column might not exist yet, ignore
        }
    }
}
