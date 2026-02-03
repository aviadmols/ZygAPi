<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RechargeService
{
    protected Store $store;

    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Get order from Recharge by Shopify Order ID
     */
    public function getOrderByShopifyId(string $shopifyOrderId): ?array
    {
        if (!$this->store->recharge_access_token) {
            Log::info("Recharge API not configured for store {$this->store->id}, skipping check");
            return null;
        }

        $url = "https://api.rechargeapps.com/orders?shopify_order_id={$shopifyOrderId}";

        try {
            $response = Http::withHeaders([
                'X-Recharge-Access-Token' => $this->store->recharge_access_token,
                'Content-Type' => 'application/json',
            ])->get($url);

            if ($response->failed()) {
                Log::warning("Recharge API returned HTTP {$response->status()}");
                return null;
            }

            $data = $response->json();
            return isset($data['orders']) && count($data['orders']) > 0 ? $data['orders'][0] : null;
        } catch (\Exception $e) {
            Log::error("Recharge API Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get subscription frequency from order
     */
    public function getSubscriptionFrequency(array $order): ?int
    {
        // Check in product properties
        if (isset($order['line_items']) && is_array($order['line_items'])) {
            foreach ($order['line_items'] as $item) {
                // Check for Shopify native subscription interval
                if (isset($item['selling_plan_allocation']['selling_plan']['billing_policy']['interval'])) {
                    $interval = $item['selling_plan_allocation']['selling_plan']['billing_policy']['interval'];
                    $intervalCount = $item['selling_plan_allocation']['selling_plan']['billing_policy']['interval_count'];
                    if ($interval == 'MONTH') {
                        return intval($intervalCount);
                    }
                }

                if (isset($item['properties']) && is_array($item['properties'])) {
                    $frequency = null;
                    $unit = null;

                    foreach ($item['properties'] as $property) {
                        if (isset($property['name']) && isset($property['value'])) {
                            $propName = strtolower($property['name']);
                            if ($propName == 'shipping_interval_frequency') {
                                $frequency = $property['value'];
                            }
                            if ($propName == 'shipping_interval_unit_type') {
                                $unit = strtolower($property['value']);
                            }
                        }
                    }

                    if ($frequency && $unit == 'month') {
                        return intval($frequency);
                    }
                }
            }
        }

        // Check in Recharge
        $rechargeOrder = $this->getOrderByShopifyId($order['id']);
        if ($rechargeOrder && isset($rechargeOrder['shipping_interval_frequency'])) {
            return intval($rechargeOrder['shipping_interval_frequency']);
        }

        return null;
    }

    /**
     * Get subscription by subscription ID
     */
    public function getSubscription(string $subscriptionId): ?array
    {
        if (!$this->store->recharge_access_token) {
            Log::info("Recharge API not configured for store {$this->store->id}");
            return null;
        }

        $url = "https://api.rechargeapps.com/subscriptions/{$subscriptionId}";

        try {
            $response = Http::withHeaders([
                'X-Recharge-Access-Token' => $this->store->recharge_access_token,
                'Content-Type' => 'application/json',
            ])->get($url);

            if ($response->failed()) {
                Log::warning("Recharge API returned HTTP {$response->status()} for subscription {$subscriptionId}");
                return null;
            }

            return $response->json()['subscription'] ?? null;
        } catch (\Exception $e) {
            Log::error("Recharge API Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get subscriptions by Shopify order ID
     */
    public function getSubscriptionsByOrderId(string $shopifyOrderId): array
    {
        if (!$this->store->recharge_access_token) {
            Log::info("Recharge API not configured for store {$this->store->id}");
            return [];
        }

        $url = "https://api.rechargeapps.com/subscriptions?shopify_order_id={$shopifyOrderId}";

        try {
            $response = Http::withHeaders([
                'X-Recharge-Access-Token' => $this->store->recharge_access_token,
                'Content-Type' => 'application/json',
            ])->get($url);

            if ($response->failed()) {
                Log::warning("Recharge API returned HTTP {$response->status()}");
                return [];
            }

            $data = $response->json();
            return $data['subscriptions'] ?? [];
        } catch (\Exception $e) {
            Log::error("Recharge API Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update subscription
     */
    public function updateSubscription(string $subscriptionId, array $updates): bool
    {
        if (!$this->store->recharge_access_token) {
            throw new \Exception("Recharge API not configured for this store");
        }

        $url = "https://api.rechargeapps.com/subscriptions/{$subscriptionId}";

        try {
            $response = Http::withHeaders([
                'X-Recharge-Access-Token' => $this->store->recharge_access_token,
                'Content-Type' => 'application/json',
            ])->put($url, ['subscription' => $updates]);

            if ($response->failed()) {
                $errorMsg = $response->body();
                Log::error("Failed to update subscription {$subscriptionId}: HTTP {$response->status()} - {$errorMsg}");
                throw new \Exception("Failed to update subscription: HTTP {$response->status()}");
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Recharge API Error updating subscription: " . $e->getMessage());
            throw $e;
        }
    }
}
