<?php

namespace App\Integrations\Shopify\Actions;

use App\Contracts\ActionInterface;
use App\Domain\Automation\ActionResult;
use App\Integrations\Shopify\ShopifyClient;

class InventoryGetAction implements ActionInterface
{
    public function __construct(
        private ShopifyClient $client
    ) {
    }

    public function name(): string
    {
        return 'shopify.inventory.get';
    }

    public function execute(array $context): ActionResult
    {
        $variantId = $context['variant_id'] ?? null;
        if (!$variantId) {
            return ActionResult::error('variant_id is required');
        }

        try {
            // First, get the variant to find its inventory_item_id
            $variantResponse = $this->client->makeRequest(
                'GET',
                "/admin/api/2024-01/variants/{$variantId}.json"
            );

            $inventoryItemId = $variantResponse['variant']['inventory_item_id'] ?? null;
            if (!$inventoryItemId) {
                return ActionResult::error('Could not find inventory_item_id for variant ' . $variantId);
            }

            // Now get inventory levels for this item
            $inventoryResponse = $this->client->makeRequest(
                'GET',
                "/admin/api/2024-01/inventory_levels.json?inventory_item_ids={$inventoryItemId}"
            );

            $inventoryLevels = $inventoryResponse['inventory_levels'] ?? [];
            $totalQuantity = array_sum(array_column($inventoryLevels, 'available'));

            return ActionResult::success(
                [
                    'inventory_item_id' => $inventoryItemId,
                    'inventory_levels' => $inventoryLevels,
                    'total_available' => $totalQuantity,
                ],
                $this->buildRequest($variantId),
                $this->buildResponse($inventoryResponse)
            );
        } catch (\Exception $e) {
            return ActionResult::error($e->getMessage());
        }
    }

    public function simulate(array $context): ActionResult
    {
        $variantId = $context['variant_id'] ?? null;
        if (!$variantId) {
            return ActionResult::error('variant_id is required');
        }

        $simulationDiff = [
            'action' => 'GET',
            'endpoint' => "/admin/api/2024-01/variants/{$variantId}.json",
            'expected_effect' => 'Retrieve variant and its inventory levels',
        ];

        return ActionResult::simulated($simulationDiff, $this->buildRequest($variantId));
    }

    public function requiredScopes(): array
    {
        return ['read_products', 'read_inventory'];
    }

    private function buildRequest($variantId): array
    {
        return [
            'method' => 'GET',
            'url' => "/admin/api/2024-01/variants/{$variantId}.json",
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
