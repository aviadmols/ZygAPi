<?php

namespace App\Jobs;

use App\Domain\Automation\Engine;
use App\Models\Run;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunAutomationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 15, 30];
    public $timeout = 300;

    public function __construct(
        public int $runId
    ) {
    }

    public function handle(Engine $engine): void
    {
        $run = Run::findOrFail($this->runId);

        if ($run->status === Run::STATUS_RUNNING) {
            Log::warning('Run already in progress', ['run_id' => $this->runId]);
            return;
        }

        try {
            $engine->execute($run);
        } catch (\Exception $e) {
            Log::error('RunAutomationJob failed', [
                'run_id' => $this->runId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $run->status = Run::STATUS_FAILED;
                $run->finished_at = now();
                $run->save();
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $run = Run::find($this->runId);
        if ($run) {
            $run->status = Run::STATUS_FAILED;
            $run->finished_at = now();
            $run->save();

            Log::error('RunAutomationJob permanently failed', [
                'run_id' => $this->runId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
