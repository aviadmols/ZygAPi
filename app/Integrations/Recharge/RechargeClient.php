<?php

namespace App\Integrations\Recharge;

use App\Models\IntegrationRecharge;
use App\Services\RedactionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RechargeClient
{
    public function __construct(
        private IntegrationRecharge $integration,
        private RedactionService $redactionService
    ) {
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest('GET', '/subscriptions');
            return isset($response['subscriptions']);
        } catch (\Exception $e) {
            Log::error('Recharge connection test failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $baseUrl = $this->integration->base_url ?: config('integrations.recharge.api_url');
        $url = rtrim($baseUrl, '/') . $endpoint;

        $requestData = [
            'method' => $method,
            'url' => $url,
            'headers' => [
                'X-Recharge-Access-Token' => $this->integration->access_token,
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
            throw new \Exception('Recharge API error: ' . $httpResponse->body());
        }

        $redactedRequest = $this->redactionService->redactRequest($requestData);
        $redactedResponse = $this->redactionService->redactResponse($responseData);

        Log::info('Recharge API request', [
            'request' => $redactedRequest,
            'response' => $redactedResponse,
        ]);

        return $responseData['body'];
    }
}
