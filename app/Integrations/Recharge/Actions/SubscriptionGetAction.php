<?php

namespace App\Integrations\Recharge\Actions;

use App\Contracts\ActionInterface;
use App\Domain\Automation\ActionResult;
use App\Integrations\Recharge\RechargeClient;

class SubscriptionGetAction implements ActionInterface
{
    public function __construct(
        private RechargeClient $client
    ) {
    }

    public function name(): string
    {
        return 'recharge.subscription.get';
    }

    public function execute(array $context): ActionResult
    {
        $subscriptionId = $context['subscription_id'] ?? null;
        if (!$subscriptionId) {
            return ActionResult::error('subscription_id is required');
        }

        try {
            $response = $this->client->makeRequest(
                'GET',
                '/subscriptions/' . $subscriptionId
            );

            return ActionResult::success(
                ['subscription' => $response['subscription'] ?? $response],
                $this->buildRequest($subscriptionId),
                $this->buildResponse($response)
            );
        } catch (\Exception $e) {
            return ActionResult::error($e->getMessage());
        }
    }

    public function simulate(array $context): ActionResult
    {
        $subscriptionId = $context['subscription_id'] ?? null;
        if (!$subscriptionId) {
            return ActionResult::error('subscription_id is required');
        }

        $simulationDiff = [
            'action' => 'GET',
            'endpoint' => '/subscriptions/' . $subscriptionId,
            'expected_effect' => 'Retrieve subscription details',
        ];

        return ActionResult::simulated($simulationDiff, $this->buildRequest($subscriptionId));
    }

    public function requiredScopes(): array
    {
        return ['read_subscriptions'];
    }

    private function buildRequest($subscriptionId): array
    {
        return [
            'method' => 'GET',
            'url' => '/subscriptions/' . $subscriptionId,
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
