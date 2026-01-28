<?php

namespace App\Integrations\Shopify\Actions;

use App\Contracts\ActionInterface;
use App\Domain\Automation\ActionResult;
use App\Integrations\Shopify\ShopifyClient;

class OrderAddTagsAction implements ActionInterface
{
    public function __construct(
        private ShopifyClient $client
    ) {
    }

    public function name(): string
    {
        return 'shopify.order.add_tags';
    }

    public function execute(array $context): ActionResult
    {
        $orderId = $context['order_id'] ?? null;
        $tags = $context['tags'] ?? [];

        if (!$orderId) {
            return ActionResult::error('order_id is required');
        }

        if (empty($tags)) {
            return ActionResult::error('tags array is required');
        }

        try {
            $response = $this->client->makeRequest(
                'PUT',
                '/admin/api/2024-01/orders/' . $orderId . '.json',
                ['order' => ['id' => $orderId, 'tags' => implode(',', $tags)]]
            );

            return ActionResult::success(
                ['order' => $response['order'] ?? $response],
                $this->buildRequest($orderId, $tags),
                $this->buildResponse($response)
            );
        } catch (\Exception $e) {
            return ActionResult::error($e->getMessage());
        }
    }

    public function simulate(array $context): ActionResult
    {
        $orderId = $context['order_id'] ?? null;
        $tags = $context['tags'] ?? [];

        if (!$orderId) {
            return ActionResult::error('order_id is required');
        }

        $simulationDiff = [
            'action' => 'PUT',
            'endpoint' => '/admin/api/2024-01/orders/' . $orderId . '.json',
            'expected_effect' => 'Add tags: ' . implode(', ', $tags),
            'dry_run' => true,
        ];

        return ActionResult::simulated($simulationDiff, $this->buildRequest($orderId, $tags));
    }

    public function requiredScopes(): array
    {
        return ['write_orders'];
    }

    private function buildRequest($orderId, $tags): array
    {
        return [
            'method' => 'PUT',
            'url' => '/admin/api/2024-01/orders/' . $orderId . '.json',
            'body' => ['tags' => $tags],
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
