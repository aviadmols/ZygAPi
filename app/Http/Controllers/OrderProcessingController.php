<?php

namespace App\Http\Controllers;

use App\Models\OrderProcessingJob;
use App\Models\Store;
use App\Jobs\ProcessBatchOrdersJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OrderProcessingController extends Controller
{
    /**
     * Process orders
     */
    public function processOrders(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'rule_id' => 'nullable|exists:tagging_rules,id',
            'order_ids' => 'required|string', // Comma-separated order IDs
            'overwrite_existing_tags' => 'boolean',
        ]);

        // Parse order IDs
        $orderIds = array_filter(array_map('trim', explode(',', $validated['order_ids'])));
        
        if (empty($orderIds)) {
            return response()->json([
                'success' => false,
                'error' => 'לא הוזנו מספרי הזמנות',
            ], 400);
        }

        // Create processing job record
        $processingJob = OrderProcessingJob::create([
            'store_id' => $validated['store_id'],
            'rule_id' => $validated['rule_id'] ?? null,
            'order_ids' => $orderIds,
            'status' => 'pending',
            'total_orders' => count($orderIds),
            'processed_orders' => 0,
            'failed_orders' => 0,
        ]);

        // Dispatch batch processing job
        ProcessBatchOrdersJob::dispatch($processingJob->id)
            ->onQueue('order-processing');

        return response()->json([
            'success' => true,
            'job_id' => $processingJob->id,
            'message' => 'עיבוד ההזמנות התחיל',
        ]);
    }

    /**
     * Get processing progress
     */
    public function getProgress(OrderProcessingJob $orderProcessingJob): JsonResponse
    {
        $progress = [
            'id' => $orderProcessingJob->id,
            'status' => $orderProcessingJob->status,
            'total_orders' => $orderProcessingJob->total_orders,
            'processed_orders' => $orderProcessingJob->processed_orders,
            'failed_orders' => $orderProcessingJob->failed_orders,
            'progress_percentage' => $orderProcessingJob->total_orders > 0 
                ? round(($orderProcessingJob->processed_orders + $orderProcessingJob->failed_orders) / $orderProcessingJob->total_orders * 100, 2)
                : 0,
            'started_at' => $orderProcessingJob->started_at?->toDateTimeString(),
            'completed_at' => $orderProcessingJob->completed_at?->toDateTimeString(),
            'details' => $orderProcessingJob->progress ?? [],
        ];

        // Check if completed
        if ($orderProcessingJob->status === 'pending' || $orderProcessingJob->status === 'processing') {
            $totalProcessed = $orderProcessingJob->processed_orders + $orderProcessingJob->failed_orders;
            if ($totalProcessed >= $orderProcessingJob->total_orders && $orderProcessingJob->total_orders > 0) {
                $orderProcessingJob->status = 'completed';
                $orderProcessingJob->completed_at = now();
                $orderProcessingJob->save();
                $progress['status'] = 'completed';
            }
        }

        return response()->json([
            'success' => true,
            'progress' => $progress,
        ]);
    }

    /**
     * Get processing results
     */
    public function getResults(OrderProcessingJob $orderProcessingJob): View|JsonResponse
    {
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'job' => $orderProcessingJob->load('store', 'rule'),
                'progress' => $orderProcessingJob->progress ?? [],
            ]);
        }

        return view('order-processing.results', [
            'job' => $orderProcessingJob->load('store', 'rule'),
        ]);
    }

    /**
     * Show order processing interface
     */
    public function index(): View
    {
        $stores = Store::where('is_active', true)->get();
        $jobs = OrderProcessingJob::with('store', 'rule')
            ->latest()
            ->paginate(20);

        return view('order-processing.index', compact('stores', 'jobs'));
    }
}
