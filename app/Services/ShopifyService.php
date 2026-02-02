<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    protected Store $store;

    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Get order from Shopify
     */
    public function getOrder(string $orderId): array
    {
        $url = "https://{$this->store->shopify_store_url}/admin/api/2024-10/orders/{$orderId}.json";

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->store->shopify_access_token,
                'Content-Type' => 'application/json',
            ])->get($url);

            if ($response->failed()) {
                $errorMsg = "Failed to fetch order {$orderId}: HTTP {$response->status()}";
                if ($response->status() == 401) {
                    $errorMsg .= " - Invalid Shopify Access Token or insufficient permissions";
                } elseif ($response->status() == 404) {
                    $errorMsg .= " - Order not found";
                }
                Log::error($errorMsg);
                throw new \Exception($errorMsg);
            }

            $data = $response->json();

            if (!isset($data['order'])) {
                throw new \Exception("Invalid response from Shopify API");
            }

            return $data['order'];
        } catch (\Exception $e) {
            Log::error("Shopify API Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get order by order number (e.g. 1005). Falls back when getOrder by ID returns 404.
     */
    public function getOrderByOrderNumber(string $orderNumber): array
    {
        $url = "https://{$this->store->shopify_store_url}/admin/api/2024-10/orders.json?name=" . urlencode($orderNumber) . "&limit=1";

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->store->shopify_access_token,
            'Content-Type' => 'application/json',
        ])->get($url);

        if ($response->failed() || !isset($response->json()['orders'][0])) {
            throw new \Exception("Order not found for order number: {$orderNumber}");
        }

        return $response->json()['orders'][0];
    }

    /**
     * Get order by ID or by order number (tries ID first, then order number).
     */
    public function getOrderByIdOrNumber(string $orderIdOrNumber): array
    {
        try {
            return $this->getOrder($orderIdOrNumber);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), '404') !== false) {
                return $this->getOrderByOrderNumber($orderIdOrNumber);
            }
            throw $e;
        }
    }

    /**
     * Update order tags in Shopify
     */
    public function updateOrderTags(string $orderId, array $tags, bool $overwrite = false): bool
    {
        $url = "https://{$this->store->shopify_store_url}/admin/api/2024-10/orders/{$orderId}.json";

        // If not overwriting, get existing tags first
        if (!$overwrite) {
            try {
                $order = $this->getOrder($orderId);
                $existingTags = isset($order['tags']) ? $order['tags'] : '';
                $existingTagsArray = array_filter(array_map('trim', explode(',', $existingTags)));
                $tags = array_unique(array_merge($existingTagsArray, $tags));
            } catch (\Exception $e) {
                Log::warning("Could not fetch existing tags, proceeding with new tags only: " . $e->getMessage());
            }
        }

        $tagsString = implode(', ', $tags);
        $orderIdForBody = is_numeric($orderId) ? (int) $orderId : $orderId;

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->store->shopify_access_token,
                'Content-Type' => 'application/json',
            ])->put($url, [
                'order' => [
                    'id' => $orderIdForBody,
                    'tags' => $tagsString,
                ],
            ]);

            if ($response->failed()) {
                $body = $response->json();
                $msg = $response->body();
                if (is_array($body) && isset($body['errors'])) {
                    $msg = is_string($body['errors']) ? $body['errors'] : json_encode($body['errors']);
                }
                Log::error("Failed to update order {$orderId} tags: HTTP {$response->status()} - {$msg}");
                throw new \Exception("Shopify HTTP {$response->status()}: " . (strlen($msg) > 200 ? substr($msg, 0, 200) . '...' : $msg));
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Shopify API Error updating tags: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all orders for a customer
     */
    public function getCustomerOrders(string $customerId): array
    {
        $allOrders = [];
        $url = "https://{$this->store->shopify_store_url}/admin/api/2024-10/orders.json?customer_id={$customerId}&status=any&limit=250";

        do {
            try {
                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $this->store->shopify_access_token,
                    'Content-Type' => 'application/json',
                ])->get($url);

                if ($response->failed()) {
                    break;
                }

                $data = $response->json();
                if (isset($data['orders'])) {
                    $allOrders = array_merge($allOrders, $data['orders']);
                }

                // Check for pagination
                $linkHeader = $response->header('Link');
                $url = $this->extractLinkHeader($linkHeader, 'next');

            } catch (\Exception $e) {
                Log::error("Error fetching customer orders: " . $e->getMessage());
                break;
            }
        } while ($url);

        return $allOrders;
    }

    /**
     * Extract Link header for pagination
     */
    protected function extractLinkHeader(?string $headers, string $rel): ?string
    {
        if (!$headers) {
            return null;
        }

        $pattern = '/<([^>]+)>;\s*rel="' . $rel . '"/';
        if (preg_match($pattern, $headers, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
