<?php

namespace App\Console\Commands;

use App\Domain\Automation\Engine;
use App\Models\Automation;
use App\Models\Run;
use App\Services\IdempotencyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class RunScheduledAutomations extends Command
{
    protected $signature = 'automations:run-scheduled';
    protected $description = 'Run scheduled automations';

    public function handle(IdempotencyService $idempotencyService, Engine $engine): int
    {
        $automations = Automation::where('status', Automation::STATUS_ACTIVE)
            ->where('trigger_type', Automation::TRIGGER_SCHEDULE)
            ->get();

        foreach ($automations as $automation) {
            $schedule = $automation->trigger_config['schedule'] ?? null;
            if (!$this->shouldRun($schedule)) {
                continue;
            }

            $payload = $automation->trigger_config['payload'] ?? [];
            $idempotencyKey = $idempotencyService->generateKey(
                $automation->shop_id,
                $automation->id,
                'schedule',
                $payload
            );

            if ($idempotencyService->checkExists($idempotencyKey)) {
                continue;
            }

            $executionSnapshot = $engine->createExecutionSnapshot($automation, $payload);

            $run = Run::create([
                'shop_id' => $automation->shop_id,
                'automation_id' => $automation->id,
                'mode' => Run::MODE_EXECUTE,
                'status' => Run::STATUS_QUEUED,
                'trigger_type' => 'schedule',
                'trigger_payload' => $payload,
                'idempotency_key' => $idempotencyKey,
                'execution_snapshot_json' => $executionSnapshot,
            ]);

            Queue::push(\App\Jobs\RunAutomationJob::class, $run->id);
        }

        return Command::SUCCESS;
    }

    private function shouldRun(?string $schedule): bool
    {
        if (!$schedule) {
            return false;
        }

        return true;
    }
}
