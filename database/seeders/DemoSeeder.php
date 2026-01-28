<?php

namespace Database\Seeders;

use App\Models\Automation;
use App\Models\IntegrationOpenrouter;
use App\Models\IntegrationShopify;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => Hash::make('password'),
        ]);

        $shop = Shop::create([
            'name' => 'Demo Shop',
            'slug' => 'demo-shop',
            'status' => 'active',
        ]);

        $shop->members()->create([
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        IntegrationShopify::create([
            'shop_id' => $shop->id,
            'shop_domain' => 'demo-shop.myshopify.com',
            'access_token' => 'demo_token_encrypted',
            'api_version' => '2024-01',
            'status' => 'active',
        ]);

        IntegrationOpenrouter::create([
            'shop_id' => $shop->id,
            'api_key' => env('OPENROUTER_API_KEY', 'demo_key'),
            'default_model' => 'anthropic/claude-3.5-sonnet',
            'status' => 'active',
        ]);

        Automation::create([
            'shop_id' => $shop->id,
            'name' => 'Demo Order Tag Automation',
            'status' => Automation::STATUS_ACTIVE,
            'trigger_type' => Automation::TRIGGER_PLAYGROUND,
            'trigger_config' => [],
            'steps' => [
                [
                    'id' => 'step-1',
                    'name' => 'Get Order',
                    'action_type' => 'shopify.order.get',
                    'enabled' => true,
                    'config' => [],
                    'input_map' => [
                        'order_id' => 'trigger_payload.order_id',
                    ],
                    'conditions' => [],
                    'retry_policy' => [
                        'max_attempts' => 3,
                        'backoff_seconds' => 5,
                    ],
                ],
                [
                    'id' => 'step-2',
                    'name' => 'Add VIP Tag',
                    'action_type' => 'shopify.order.add_tags',
                    'enabled' => true,
                    'config' => [],
                    'input_map' => [
                        'order_id' => 'trigger_payload.order_id',
                        'tags' => 'trigger_payload.tags',
                    ],
                    'conditions' => [],
                    'retry_policy' => [
                        'max_attempts' => 3,
                        'backoff_seconds' => 5,
                    ],
                ],
            ],
            'version' => 1,
        ]);
    }
}
