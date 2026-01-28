<?php

namespace App\Integrations\Shopify\Actions;

use App\Contracts\ActionInterface;
use App\Domain\Automation\ActionResult;
use App\Integrations\Shopify\ShopifyClient;

class OrderGetAction implements ActionInterface
{
    public function __construct(
        private ShopifyClient $client
    ) {
    }

    public function name(): string
    {
        return 'shopify.order.get';
    }

    public function execute(array $context): ActionResult
    {
        $orderId = $context['order_id'] ?? null;
        if (!$orderId) {
            return ActionResult::error('order_id is required');
        }

        try {
            $response = $this->client->makeRequest(
                'GET',
                '/admin/api/2024-01/orders/' . $orderId . '.json'
            );

            return ActionResult::success(
                ['order' => $response['order'] ?? $response],
                $this->buildRequest($orderId),
                $this->buildResponse($response)
            );
        } catch (\Exception $e) {
            return ActionResult::error($e->getMessage());
        }
    }

    public function simulate(array $context): ActionResult
    {
        $orderId = $context['order_id'] ?? null;
        if (!$orderId) {
            return ActionResult::error('order_id is required');
        }

        $simulationDiff = [
            'action' => 'GET',
            'endpoint' => '/admin/api/2024-01/orders/' . $orderId . '.json',
            'expected_effect' => 'Retrieve order details',
        ];

        return ActionResult::simulated($simulationDiff, $this->buildRequest($orderId));
    }

    public function requiredScopes(): array
    {
        return ['read_orders'];
    }

    private function buildRequest($orderId): array
    {
        return [
            'method' => 'GET',
            'url' => '/admin/api/2024-01/orders/' . $orderId . '.json',
        ];
    }

    private function buildResponse($response): array
    {
        return [
            'status' => 200,
            'body' => $response,
        ];
    }
}
