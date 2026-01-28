<?php

namespace App\Integrations\Shopify;

use App\Models\IntegrationShopify;
use App\Services\RedactionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyClient
{
    public function __construct(
        private IntegrationShopify $integration,
        private RedactionService $redactionService
    ) {
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest('GET', '/admin/api/' . $this->integration->api_version . '/shop.json');
            return isset($response['shop']);
        } catch (\Exception $e) {
            Log::error('Shopify connection test failed', [
                'error' => $e->getMessage(),
                'shop_domain' => $this->integration->shop_domain,
            ]);
            return false;
        }
    }

    public function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = 'https://' . $this->integration->shop_domain . $endpoint;

        $requestData = [
            'method' => $method,
            'url' => $url,
            'headers' => [
                'X-Shopify-Access-Token' => $this->integration->access_token,
                'Content-Type' => 'application/json',
            ],
        ];

        if (!empty($data)) {
            $requestData['body'] = $data;
        }

        $httpResponse = Http::withHeaders($requestData['headers'])
            ->{strtolower($method)}($url, $data);

        $responseData = [
            'status' => $httpResponse->status(),
            'headers' => $httpResponse->headers(),
            'body' => $httpResponse->json() ?? $httpResponse->body(),
        ];

        if ($httpResponse->status() === 429) {
            $retryAfter = $httpResponse->header('Retry-After');
            if ($retryAfter) {
                sleep((int) $retryAfter);
                return $this->makeRequest($method, $endpoint, $data);
            }
        }

        if (!$httpResponse->successful()) {
            throw new \Exception('Shopify API error: ' . $httpResponse->body());
        }

        $redactedRequest = $this->redactionService->redactRequest($requestData);
        $redactedResponse = $this->redactionService->redactResponse($responseData);

        Log::info('Shopify API request', [
            'request' => $redactedRequest,
            'response' => $redactedResponse,
        ]);

        return $responseData['body'];
    }
}
