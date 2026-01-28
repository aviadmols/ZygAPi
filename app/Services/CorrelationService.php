<?php

namespace App\Services;

class CorrelationService
{
    public function extractOrderId(array $payload): ?string
    {
        return $payload['order_id'] ?? $payload['id'] ?? $payload['order']['id'] ?? null;
    }

    public function extractSubscriptionId(array $payload): ?string
    {
        return $payload['subscription_id'] ?? $payload['id'] ?? $payload['subscription']['id'] ?? null;
    }

    public function extractCustomerId(array $payload): ?string
    {
        return $payload['customer_id'] ?? $payload['customer']['id'] ?? $payload['order']['customer_id'] ?? null;
    }

    public function extractOrderNumber(array $payload): ?string
    {
        return $payload['order_number'] ?? $payload['name'] ?? $payload['order']['name'] ?? null;
    }

    public function correlateRun($run, array $payload): void
    {
        $run->external_order_id = $this->extractOrderId($payload);
        $run->order_number = $this->extractOrderNumber($payload);
        $run->external_subscription_id = $this->extractSubscriptionId($payload);
        $run->customer_id = $this->extractCustomerId($payload);
        $run->save();
    }
}
