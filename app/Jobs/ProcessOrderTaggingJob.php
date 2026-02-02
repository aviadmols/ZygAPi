<?php

namespace App\Jobs;

use App\Models\OrderProcessingJob;
use App\Models\Store;
use App\Models\TaggingRule;
use App\Services\ShopifyService;
use App\Services\TaggingEngineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessOrderTaggingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $storeId,
        public string $orderId,
        public ?int $ruleId = null,
        public ?int $processingJobId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $store = Store::findOrFail($this->storeId);
            $shopifyService = new ShopifyService($store);
            $taggingEngine = new TaggingEngineService();

            // Get order from Shopify (by ID or order number)
            $order = $shopifyService->getOrderByIdOrNumber($this->orderId);

            // Get rule if specified
            if ($this->ruleId) {
                $rule = TaggingRule::findOrFail($this->ruleId);
                if (!$rule->is_active) {
                    Log::info("Rule {$this->ruleId} is not active, skipping order {$this->orderId}");
                    return;
                }
            } else {
                // Get all active rules for the store
                $rules = $store->taggingRules()->where('is_active', true)->get();
                
                if ($rules->isEmpty()) {
                    Log::info("No active rules found for store {$this->storeId}, skipping order {$this->orderId}");
                    return;
                }

                // Process with all active rules
                foreach ($rules as $rule) {
                    $taggingEngine->processOrder($order, $rule, $shopifyService);
                }

                // Update processing job progress if exists
                if ($this->processingJobId) {
                    $this->updateProgress(true);
                }

                return;
            }

            // Process order with specific rule
            $success = $taggingEngine->processOrder($order, $rule, $shopifyService);

            // Update processing job progress if exists
            if ($this->processingJobId) {
                $this->updateProgress($success);
            }

            if (!$success) {
                throw new \Exception("Failed to process order {$this->orderId}");
            }

        } catch (\Exception $e) {
            Log::error("Error processing order {$this->orderId}: " . $e->getMessage());

            // Update processing job progress if exists
            if ($this->processingJobId) {
                $this->updateProgress(false);
            }

            throw $e;
        }
    }

    /**
     * Update processing job progress
     */
    protected function updateProgress(bool $success): void
    {
        if (!$this->processingJobId) {
            return;
        }

        $processingJob = OrderProcessingJob::find($this->processingJobId);
        if (!$processingJob) {
            return;
        }

        $processingJob->increment($success ? 'processed_orders' : 'failed_orders');

        $progress = $processingJob->progress ?? [];
        $progress[] = [
            'order_id' => $this->orderId,
            'success' => $success,
            'processed_at' => now()->toDateTimeString(),
        ];
        $processingJob->progress = $progress;
        $processingJob->save();
    }
}
