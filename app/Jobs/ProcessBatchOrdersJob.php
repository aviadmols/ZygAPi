<?php

namespace App\Jobs;

use App\Models\OrderProcessingJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessBatchOrdersJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $processingJobId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $processingJob = OrderProcessingJob::findOrFail($this->processingJobId);
        
        if ($processingJob->status !== 'pending') {
            Log::warning("Processing job {$this->processingJobId} is not in pending status");
            return;
        }

        // Update status to processing
        $processingJob->status = 'processing';
        $processingJob->started_at = now();
        $processingJob->save();

        $orderIds = $processingJob->order_ids ?? [];
        $totalOrders = count($orderIds);
        $processingJob->total_orders = $totalOrders;
        $processingJob->save();

        // Dispatch individual order processing jobs
        foreach ($orderIds as $orderId) {
            ProcessOrderTaggingJob::dispatch(
                $processingJob->store_id,
                $orderId,
                $processingJob->rule_id,
                $this->processingJobId
            )->onQueue('order-processing');
        }

        // Note: We don't mark as completed here - individual jobs will update progress
        // A separate job or scheduled task should check and mark as completed when all are done
    }
}
