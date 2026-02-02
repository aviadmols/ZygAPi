<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\TaggingRule;
use App\Services\OpenRouterService;
use App\Services\ShopifyService;
use App\Services\TaggingEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class TaggingRuleController extends Controller
{
    public function __construct(
        protected OpenRouterService $openRouterService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = TaggingRule::with('store');

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $rules = $query->latest()->get();

        if ($request->wantsJson()) {
            return response()->json($rules);
        }

        $rules = $query->latest()->paginate(15);
        return view('tagging-rules.index', compact('rules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View|\Illuminate\Http\Response
    {
        try {
            $stores = Store::where('is_active', true)->orderBy('name')->get();
        } catch (\Throwable $e) {
            Log::error('TaggingRuleController::create - could not load stores: ' . $e->getMessage());
            $stores = collect();
        }

        try {
            return view('tagging-rules.create', compact('stores'));
        } catch (\Throwable $e) {
            Log::error('TaggingRuleController::create view error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $msg = config('app.debug') ? '<h1>Error loading create form</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>' : '<h1>Something went wrong</h1><p>Set APP_DEBUG=true on Railway to see details, or run: php artisan migrate</p>';
            return response('<html><body>' . $msg . '</body></html>', 500, ['Content-Type' => 'text/html; charset=utf-8']);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rules_json' => 'nullable|string',
            'tags_template' => 'nullable|string',
            'php_rule' => 'nullable|string',
            'is_active' => 'boolean',
            'overwrite_existing_tags' => 'boolean',
        ]);

        if (!empty($validated['rules_json'])) {
            $decoded = json_decode($validated['rules_json'], true);
            $validated['rules_json'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
        }

        TaggingRule::create($validated);

        return redirect()->route('tagging-rules.index')
            ->with('success', 'Rule created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(TaggingRule $taggingRule): View
    {
        return view('tagging-rules.show', compact('taggingRule'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TaggingRule $taggingRule): View|\Illuminate\Http\Response
    {
        try {
            $stores = Store::where('is_active', true)->orderBy('name')->get();
        } catch (\Throwable $e) {
            Log::error('TaggingRuleController::edit - could not load stores: ' . $e->getMessage());
            $stores = collect();
        }

        try {
            return view('tagging-rules.edit', compact('taggingRule', 'stores'));
        } catch (\Throwable $e) {
            Log::error('TaggingRuleController::edit view error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $msg = config('app.debug') ? '<h1>Error loading edit form</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>' : '<h1>Something went wrong</h1><p>Set APP_DEBUG=true to see details.</p>';
            return response('<html><body>' . $msg . '</body></html>', 500, ['Content-Type' => 'text/html; charset=utf-8']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TaggingRule $taggingRule): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rules_json' => 'nullable|string',
            'tags_template' => 'nullable|string',
            'php_rule' => 'nullable|string',
            'is_active' => 'boolean',
            'overwrite_existing_tags' => 'boolean',
        ]);

        if (!empty($validated['rules_json'])) {
            $decoded = json_decode($validated['rules_json'], true);
            $validated['rules_json'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
        }

        $taggingRule->update($validated);

        return redirect()->route('tagging-rules.index')
            ->with('success', 'Rule updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TaggingRule $taggingRule): RedirectResponse
    {
        $taggingRule->delete();

        return redirect()->route('tagging-rules.index')
            ->with('success', 'Rule deleted successfully');
    }

    /**
     * Preview tags for an order using rule data (before saving). Used on create form.
     * Supports rules_json + tags_template, or php_rule (PHP code).
     */
    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'order_id' => 'required|string',
            'rules_json' => 'nullable|string',
            'tags_template' => 'nullable|string',
            'php_rule' => 'nullable|string',
        ]);

        try {
            $store = Store::findOrFail($validated['store_id']);
            $shopifyService = new ShopifyService($store);
            $taggingEngine = new TaggingEngineService();

            $order = $shopifyService->getOrder($validated['order_id']);

            $rulesJson = null;
            if (!empty($validated['rules_json'])) {
                $decoded = json_decode($validated['rules_json'], true);
                $rulesJson = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
            }

            $rule = new TaggingRule();
            $rule->rules_json = $rulesJson;
            $rule->tags_template = $validated['tags_template'] ?? null;
            $rule->php_rule = $validated['php_rule'] ?? null;
            $rule->overwrite_existing_tags = false;

            $tags = $taggingEngine->extractTags($order, $rule);

            return response()->json([
                'success' => true,
                'tags' => $tags,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate PHP rule from order sample and user requirements (AI).
     */
    public function generatePhp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'order_id' => 'nullable|string',
            'order_json' => 'nullable|string',
            'requirements' => 'required|string',
        ]);

        try {
            $orderData = null;
            if (!empty($validated['order_json'])) {
                $orderData = json_decode($validated['order_json'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json(['success' => false, 'error' => 'Invalid order JSON.'], 422);
                }
            }
            if ($orderData === null && !empty($validated['store_id']) && !empty($validated['order_id'])) {
                $store = Store::findOrFail($validated['store_id']);
                $shopifyService = new ShopifyService($store);
                $orderData = $shopifyService->getOrder($validated['order_id']);
            }
            if ($orderData === null) {
                return response()->json(['success' => false, 'error' => 'Provide either order_json or store_id + order_id to fetch an order.'], 422);
            }

            $result = $this->openRouterService->generatePhpRule($orderData, $validated['requirements']);
            return response()->json([
                'success' => true,
                'php_code' => $result['php_code'],
            ]);
        } catch (\Exception $e) {
            Log::error('TaggingRuleController::generatePhp: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test rule on a specific order (for saved rule)
     */
    public function test(Request $request, TaggingRule $taggingRule): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|string',
        ]);

        try {
            $store = $taggingRule->store;
            $shopifyService = new ShopifyService($store);
            $taggingEngine = new TaggingEngineService();

            $order = $shopifyService->getOrder($validated['order_id']);
            $tags = $taggingEngine->extractTags($order, $taggingRule);

            return response()->json([
                'success' => true,
                'tags' => $tags,
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
