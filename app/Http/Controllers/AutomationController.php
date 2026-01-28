<?php

namespace App\Http\Controllers;

use App\Domain\Automation\Engine;
use App\Models\Automation;
use App\Models\Run;
use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

class AutomationController extends Controller
{
    public function __construct(
        private Engine $engine,
        private IdempotencyService $idempotencyService
    ) {
    }

    public function runNow(Request $request, Automation $automation): JsonResponse
    {
        $request->validate([
            'mode' => 'required|in:dry_run,execute',
            'payload' => 'nullable|array',
            'order_ids' => 'nullable|array',
            'order_ids.*' => 'string',
        ]);

        $payload = $request->payload ?? [];
        $orderIds = $request->order_ids ?? [];

        if (!empty($orderIds)) {
            return $this->runForOrders($automation, $orderIds, $request->mode);
        }

        $idempotencyKey = $this->idempotencyService->generateKey(
            $automation->shop_id,
            $automation->id,
            'manual',
            $payload
        );

        $existingRun = $this->idempotencyService->checkExists($idempotencyKey);
        if ($existingRun) {
            return response()->json([
                'run_id' => $existingRun->id,
                'message' => 'Run already exists',
            ]);
        }

        $executionSnapshot = $this->engine->createExecutionSnapshot($automation, $payload);

        $run = Run::create([
            'shop_id' => $automation->shop_id,
            'automation_id' => $automation->id,
            'mode' => $request->mode,
            'status' => Run::STATUS_QUEUED,
            'trigger_type' => 'manual',
            'trigger_payload' => $payload,
            'idempotency_key' => $idempotencyKey,
            'execution_snapshot_json' => $executionSnapshot,
        ]);

        Queue::push(\App\Jobs\RunAutomationJob::class, $run->id);

        return response()->json([
            'run_id' => $run->id,
            'status' => $run->status,
            'message' => 'Automation queued for execution',
        ]);
    }

    public function retry(Run $run): JsonResponse
    {
        if ($run->status !== Run::STATUS_FAILED) {
            return response()->json([
                'error' => 'Can only retry failed runs',
            ], 400);
        }

        $newRun = Run::create([
            'shop_id' => $run->shop_id,
            'automation_id' => $run->automation_id,
            'mode' => $run->mode,
            'status' => Run::STATUS_QUEUED,
            'trigger_type' => 'manual',
            'trigger_payload' => $run->trigger_payload,
            'execution_snapshot_json' => $run->execution_snapshot_json,
        ]);

        Queue::push(\App\Jobs\RunAutomationJob::class, $newRun->id);

        return response()->json([
            'run_id' => $newRun->id,
            'status' => $newRun->status,
            'message' => 'Run queued for retry',
        ]);
    }

    private function runForOrders(Automation $automation, array $orderIds, string $mode): JsonResponse
    {
        $runIds = [];

        foreach ($orderIds as $orderId) {
            $payload = ['order_id' => $orderId, 'id' => $orderId];

            $idempotencyKey = $this->idempotencyService->generateKey(
                $automation->shop_id,
                $automation->id,
                'manual',
                $payload
            );

            if ($this->idempotencyService->checkExists($idempotencyKey)) {
                continue;
            }

            $executionSnapshot = $this->engine->createExecutionSnapshot($automation, $payload);

            $run = Run::create([
                'shop_id' => $automation->shop_id,
                'automation_id' => $automation->id,
                'mode' => $mode,
                'status' => Run::STATUS_QUEUED,
                'trigger_type' => 'manual',
                'trigger_payload' => $payload,
                'idempotency_key' => $idempotencyKey,
                'execution_snapshot_json' => $executionSnapshot,
            ]);

            Queue::push(\App\Jobs\RunAutomationJob::class, $run->id);
            $runIds[] = $run->id;
        }

        return response()->json([
            'run_ids' => $runIds,
            'count' => count($runIds),
            'message' => count($runIds) . ' automations queued for execution',
        ]);
    }

    public function getWebhookUrl(Automation $automation): JsonResponse
    {
        if ($automation->trigger_type !== 'webhook') {
            return response()->json([
                'error' => 'Automation is not a webhook trigger',
            ], 400);
        }

        $event = $automation->trigger_config['event'] ?? null;
        if (!$event) {
            return response()->json([
                'error' => 'Webhook event not configured',
            ], 400);
        }

        $shop = $automation->shop;
        $baseUrl = config('app.url');
        
        $url = match (true) {
            str_starts_with($event, 'orders/') => "{$baseUrl}/webhooks/shopify/{$shop->id}/{$event}",
            str_starts_with($event, 'subscription/') => "{$baseUrl}/webhooks/recharge/{$shop->id}/{$event}",
            default => null,
        };

        if (!$url) {
            return response()->json([
                'error' => 'Unknown webhook event type',
            ], 400);
        }

        return response()->json([
            'webhook_url' => $url,
            'event' => $event,
            'shop_id' => $shop->id,
            'shop_slug' => $shop->slug,
            'instructions' => 'Configure this URL in your Shopify/Recharge webhook settings',
        ]);
    }
}
