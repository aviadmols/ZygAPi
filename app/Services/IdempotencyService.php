<?php

namespace App\Services;

use App\Models\Run;
use Illuminate\Support\Facades\Hash;

class IdempotencyService
{
    public function generateKey(int $shopId, int $automationId, string $triggerType, array $payload): string
    {
        $data = [
            'shop_id' => $shopId,
            'automation_id' => $automationId,
            'trigger_type' => $triggerType,
            'event_id' => $payload['id'] ?? $payload['event_id'] ?? null,
            'external_order_id' => $payload['order_id'] ?? $payload['id'] ?? null,
            'external_subscription_id' => $payload['subscription_id'] ?? null,
        ];

        $key = json_encode($data);
        return hash('sha256', $key);
    }

    public function checkExists(string $key): ?Run
    {
        return Run::where('idempotency_key', $key)->first();
    }

    public function markProcessing(string $key, Run $run): void
    {
        $run->idempotency_key = $key;
        $run->save();
    }
}
