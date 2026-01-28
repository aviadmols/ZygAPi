<?php

namespace App\Http\Controllers;

use App\Domain\Automation\Engine;
use App\Domain\Chat\AutomationChatService;
use App\Models\Automation;
use App\Models\Run;
use App\Services\IdempotencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class PlaygroundController extends Controller
{
    public function __construct(
        private AutomationChatService $chatService,
        private Engine $engine,
        private IdempotencyService $idempotencyService
    ) {
    }

    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'automation_id' => 'required|exists:automations,id',
            'payload_json' => 'required|json',
        ]);

        try {
            $payload = json_decode($request->payload_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'Invalid JSON payload'], 400);
            }

            $session = $this->chatService->analyzePayload(
                $request->shop_id,
                $request->automation_id,
                $payload
            );

            $patch = json_decode($session->messages()->where('role', 'assistant')->latest()->first()->content, true);

            return response()->json([
                'chat_session_id' => $session->id,
                'patch_suggestion' => $patch,
            ]);
        } catch (\Exception $e) {
            Log::error('Playground analyze failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function run(Request $request): JsonResponse
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'automation_id' => 'required|exists:automations,id',
            'payload_json' => 'required|json',
            'mode' => 'required|in:dry_run,execute',
        ]);

        try {
            $payload = json_decode($request->payload_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'Invalid JSON payload'], 400);
            }

            $automation = Automation::findOrFail($request->automation_id);
            $idempotencyKey = $this->idempotencyService->generateKey(
                $request->shop_id,
                $request->automation_id,
                'playground',
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
                'shop_id' => $request->shop_id,
                'automation_id' => $request->automation_id,
                'mode' => $request->mode,
                'status' => Run::STATUS_QUEUED,
                'trigger_type' => 'playground',
                'trigger_payload' => $payload,
                'idempotency_key' => $idempotencyKey,
                'execution_snapshot_json' => $executionSnapshot,
            ]);

            Queue::push(\App\Jobs\RunAutomationJob::class, $run->id);

            return response()->json([
                'run_id' => $run->id,
                'status' => $run->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Playground run failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
