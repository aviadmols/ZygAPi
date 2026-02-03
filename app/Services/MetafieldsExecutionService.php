<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Log;

class MetafieldsExecutionService
{
    /**
     * Execute PHP code for metafields update
     * Returns array of metafields to update
     */
    public function executePhpRule(string $phpCode, array $order, Store $store): array
    {
        $shopDomain = $store->shopify_store_url;
        $accessToken = $store->shopify_access_token;
        $metafields = [];

        // Execute PHP code in isolated scope
        try {
            eval($phpCode);
        } catch (\Throwable $e) {
            Log::error("Metafields PHP execution error: " . $e->getMessage());
            throw new \Exception("PHP execution error: " . $e->getMessage());
        }

        if (!isset($metafields) || !is_array($metafields)) {
            return [];
        }

        return $metafields;
    }
}
