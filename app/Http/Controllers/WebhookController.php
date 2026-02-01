<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\TaggingRule;
use App\Jobs\ProcessOrderTaggingJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class WebhookController extends Controller
{
    /**
     * Handle Shopify order created webhook
     */
    public function handleOrderCreated(Request $request): JsonResponse
    {
        // Verify HMAC signature
        $hmac = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();
        $secret = config('shopify.webhook_secret');

        if ($secret && !$this->verifyHmac($data, $hmac, $secret)) {
            Log::warning('Invalid webhook HMAC signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $orderData = $request->json()->all();

        // Extract store domain from webhook
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        
        if (!$shopDomain) {
            Log::error('Missing shop domain in webhook');
            return response()->json(['error' => 'Missing shop domain'], 400);
        }

        // Find store by domain
        $store = Store::where('shopify_store_url', $shopDomain)
            ->where('is_active', true)
            ->first();

        if (!$store) {
            Log::warning("Store not found for domain: {$shopDomain}");
            return response()->json(['error' => 'Store not found'], 404);
        }

        // Get order ID
        $orderId = $orderData['id'] ?? null;
        if (!$orderId) {
            Log::error('Missing order ID in webhook');
            return response()->json(['error' => 'Missing order ID'], 400);
        }

        // Get active rules for this store
        $rules = TaggingRule::where('store_id', $store->id)
            ->where('is_active', true)
            ->get();

        if ($rules->isEmpty()) {
            Log::info("No active rules found for store {$store->id}, skipping order {$orderId}");
            return response()->json(['message' => 'No active rules'], 200);
        }

        // Dispatch job for each rule (or one job for all rules if rule_id is null)
        foreach ($rules as $rule) {
            ProcessOrderTaggingJob::dispatch(
                $store->id,
                $orderId,
                $rule->id
            )->onQueue('order-processing');
        }

        Log::info("Dispatched order processing job for order {$orderId} from store {$store->id}");

        return response()->json(['message' => 'Webhook processed'], 200);
    }

    /**
     * Verify HMAC signature
     */
    protected function verifyHmac(string $data, ?string $hmac, string $secret): bool
    {
        if (!$hmac) {
            return false;
        }

        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $secret, true));
        
        return hash_equals($calculatedHmac, $hmac);
    }
}
