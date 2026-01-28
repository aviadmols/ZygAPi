<?php

namespace App\Http\Controllers;

use App\Models\Run;
use App\Models\Shop;
use App\Services\IdempotencyService;
use App\Domain\Automation\Engine;
use App\Models\Automation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class WebhookController extends Controller
{
    public function __construct(
        private IdempotencyService $idempotencyService,
        private Engine $engine
    ) {
    }

    public function handleShopify(Request $request, Shop $shop, string $event): JsonResponse
    {
        try {
            $payload = $request->all();
            $automations = Automation::where('shop_id', $shop->id)
                ->where('status', Automation::STATUS_ACTIVE)
                ->where('trigger_type', 'webhook')
                ->whereJsonContains('trigger_config->event', $event)
                ->get();

            foreach ($automations as $automation) {
                $idempotencyKey = $this->idempotencyService->generateKey(
                    $shop->id,
                    $automation->id,
                    'webhook',
                    $payload
                );

                if ($this->idempotencyService->checkExists($idempotencyKey)) {
                    continue;
                }

                $executionSnapshot = $this->engine->createExecutionSnapshot($automation, $payload);

                $run = Run::create([
                    'shop_id' => $shop->id,
                    'automation_id' => $automation->id,
                    'mode' => Run::MODE_EXECUTE,
                    'status' => Run::STATUS_QUEUED,
                    'trigger_type' => 'webhook',
                    'trigger_payload' => $payload,
                    'idempotency_key' => $idempotencyKey,
                    'execution_snapshot_json' => $executionSnapshot,
                ]);

                Queue::push(\App\Jobs\RunAutomationJob::class, $run->id);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Webhook handling failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    public function handleRecharge(Request $request, Shop $shop, string $event): JsonResponse
    {
        return $this->handleShopify($request, $shop, $event);
    }
}
