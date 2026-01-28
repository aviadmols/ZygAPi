<?php

namespace App\Domain\Automation;

use App\Contracts\ActionInterface;
use App\Integrations\Recharge\Actions\SubscriptionGetAction;
use App\Integrations\Recharge\RechargeClient;
use App\Integrations\Shopify\Actions\OrderAddTagsAction;
use App\Integrations\Shopify\Actions\OrderGetAction;
use App\Integrations\Shopify\ShopifyClient;
use App\Models\IntegrationRecharge;
use App\Models\IntegrationShopify;
use Illuminate\Support\Facades\Log;

class StepRunner
{
    public function runStep(array $step, array $context, bool $dryRun): ActionResult
    {
        $action = $this->getActionInstance($step['action_type'], $context);

        if ($dryRun) {
            return $action->simulate($context);
        }

        $retryPolicy = $step['retry_policy'] ?? [];
        $maxAttempts = $retryPolicy['max_attempts'] ?? 1;
        $backoffSeconds = $retryPolicy['backoff_seconds'] ?? 0;

        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            try {
                $result = $action->execute($context);

                if ($result->isSuccess()) {
                    return $result;
                }

                if ($attempt < $maxAttempts - 1) {
                    $retryOnStatusCodes = $retryPolicy['retry_on_status_codes'] ?? [];
                    $shouldRetry = empty($retryOnStatusCodes) || 
                        in_array($result->httpResponse['status'] ?? 0, $retryOnStatusCodes);

                    if ($shouldRetry) {
                        $attempt++;
                        if ($backoffSeconds > 0) {
                            sleep($backoffSeconds);
                        }
                        continue;
                    }
                }

                return $result;
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < $maxAttempts && $backoffSeconds > 0) {
                    sleep($backoffSeconds);
                }
            }
        }

        if ($lastException) {
            return ActionResult::error($lastException->getMessage());
        }

        return ActionResult::error('Step execution failed after ' . $maxAttempts . ' attempts');
    }

    private function getActionInstance(string $actionType, array $context): ActionInterface
    {
        $redactionService = app(\App\Services\RedactionService::class);

        return match ($actionType) {
            'shopify.order.get' => new OrderGetAction(
                new ShopifyClient(
                    $this->getShopifyIntegration($context['shop_id']),
                    $redactionService
                )
            ),
            'shopify.order.add_tags' => new OrderAddTagsAction(
                new ShopifyClient(
                    $this->getShopifyIntegration($context['shop_id']),
                    $redactionService
                )
            ),
            'recharge.subscription.get' => new SubscriptionGetAction(
                new RechargeClient(
                    $this->getRechargeIntegration($context['shop_id']),
                    $redactionService
                )
            ),
            default => throw new \Exception("Unknown action type: {$actionType}"),
        };
    }

    private function getShopifyIntegration(int $shopId): IntegrationShopify
    {
        $integration = IntegrationShopify::where('shop_id', $shopId)->first();
        if (!$integration) {
            throw new \Exception("Shopify integration not found for shop {$shopId}");
        }
        return $integration;
    }

    private function getRechargeIntegration(int $shopId): IntegrationRecharge
    {
        $integration = IntegrationRecharge::where('shop_id', $shopId)->first();
        if (!$integration) {
            throw new \Exception("Recharge integration not found for shop {$shopId}");
        }
        return $integration;
    }
}
