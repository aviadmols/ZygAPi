<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Log;

class RechargeExecutionService
{
    /**
     * Execute PHP code for Recharge subscription updates
     * Returns array of subscription updates
     */
    public function executePhpRule(string $phpCode, array $order, Store $store): array
    {
        $shopDomain = $store->shopify_store_url;
        $accessToken = $store->shopify_access_token;
        $rechargeAccessToken = $store->recharge_access_token ?? '';
        $subscriptionUpdates = [];

        // Execute PHP code in isolated scope
        try {
            eval($phpCode);
        } catch (\Throwable $e) {
            Log::error("Recharge PHP execution error: " . $e->getMessage());
            throw new \Exception("PHP execution error: " . $e->getMessage());
        }

        if (!isset($subscriptionUpdates) || !is_array($subscriptionUpdates)) {
            return [];
        }

        return $subscriptionUpdates;
    }
}
