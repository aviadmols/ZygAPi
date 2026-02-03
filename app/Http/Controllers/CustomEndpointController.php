<?php

namespace App\Http\Controllers;

use App\Models\CustomEndpoint;
use App\Models\Store;
use App\Services\OpenRouterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class CustomEndpointController extends Controller
{
    public function __construct(
        protected OpenRouterService $openRouterService
    ) {}

    public function index(): View
    {
        $endpoints = CustomEndpoint::with('store')->latest()->paginate(15);
        return view('custom-endpoints.index', compact('endpoints'));
    }

    public function create(): View
    {
        $stores = Store::where('is_active', true)->orderBy('name')->get();
        return view('custom-endpoints.create', compact('stores'));
    }

    public function build(Store $store): View
    {
        return view('custom-endpoints.build', compact('store'));
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'platform' => 'required|in:shopify,recharge',
            'prompt' => 'required|string',
            'input_params' => 'nullable|array',
            'input_params.*.name' => 'required_with:input_params|string',
            'test_return_values' => 'nullable|array',
            'test_return_values.*.name' => 'required_with:test_return_values|string',
            'test_return_values.*.value' => 'nullable|string',
        ]);

        try {
            $result = $this->openRouterService->generateCustomEndpointCode(
                $validated['platform'],
                $validated['prompt'],
                $validated['input_params'] ?? [],
                $validated['test_return_values'] ?? []
            );
            return response()->json([
                'success' => true,
                'php_code' => $result['php_code'],
                'http_method' => $result['http_method'] ?? 'POST',
            ]);
        } catch (\Throwable $e) {
            Log::error('CustomEndpointController::generate ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $inputParams = $request->input('input_params');
        $testReturnValues = $request->input('test_return_values');
        if (is_string($inputParams)) {
            $inputParams = json_decode($inputParams, true) ?: [];
        }
        if (is_string($testReturnValues)) {
            $testReturnValues = json_decode($testReturnValues, true) ?: [];
        }

        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'platform' => 'required|in:shopify,recharge',
            'prompt' => 'required|string',
            'php_code' => 'required|string',
            'webhook_token' => 'nullable|string|max:64',
        ]);

        $validated['slug'] = $validated['slug'] ?? CustomEndpoint::generateSlug($validated['name']);
        $validated['input_params'] = $inputParams;
        $validated['test_return_values'] = $testReturnValues;
        $validated['is_active'] = true;

        CustomEndpoint::create($validated);
        return redirect()->route('custom-endpoints.index')->with('success', 'Custom endpoint created.');
    }

    public function show(CustomEndpoint $customEndpoint): View
    {
        $customEndpoint->load('store');
        return view('custom-endpoints.show', compact('customEndpoint'));
    }

    public function edit(CustomEndpoint $customEndpoint): View
    {
        $stores = Store::where('is_active', true)->orderBy('name')->get();
        return view('custom-endpoints.edit', compact('customEndpoint', 'stores'));
    }

    public function update(Request $request, CustomEndpoint $customEndpoint): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'platform' => 'required|in:shopify,recharge',
            'prompt' => 'required|string',
            'php_code' => 'required|string',
            'webhook_token' => 'nullable|string|max:64',
            'is_active' => 'nullable|boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $customEndpoint->update($validated);
        return redirect()->route('custom-endpoints.index')->with('success', 'Endpoint updated.');
    }

    public function destroy(CustomEndpoint $customEndpoint): RedirectResponse
    {
        $customEndpoint->delete();
        return redirect()->route('custom-endpoints.index')->with('success', 'Endpoint deleted.');
    }

    /**
     * Test endpoint from dashboard: run with given input and return response + detailed log.
     */
    public function test(Request $request, CustomEndpoint $customEndpoint): JsonResponse
    {
        $input = $request->input('input', $request->all());
        if (is_string($input)) {
            $input = json_decode($input, true) ?: [];
        }
        $log = [];
        $log[] = ['step' => 'Request received', 'input' => $input, 'timestamp' => now()->toIso8601String()];

        $store = $customEndpoint->store;
        $shopDomain = $store->shopify_store_url ?? '';
        $accessToken = $store->shopify_access_token ?? '';
        $rechargeAccessToken = $store->recharge_access_token ?? '';

        $code = preg_replace('/^<\?php\s*/i', '', trim($customEndpoint->php_code ?? ''));
        $code = preg_replace('/\?>\s*$/i', '', $code);

        $response = [];
        $start = microtime(true);
        try {
            eval($code);
            $log[] = ['step' => 'Code executed successfully', 'duration_ms' => round((microtime(true) - $start) * 1000)];
            $log[] = ['step' => 'Response', 'response' => $response];
            return response()->json([
                'success' => true,
                'response' => $response,
                'log' => $log,
            ]);
        } catch (\Throwable $e) {
            $log[] = ['step' => 'Error', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'duration_ms' => round((microtime(true) - $start) * 1000)];
            Log::error('CustomEndpoint test error', ['id' => $customEndpoint->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'log' => $log,
            ], 500);
        }
    }

    /**
     * Execute custom endpoint (webhook). Auth: X-Webhook-Token header or ?token=.
     */
    public function execute(Request $request, string $slug): JsonResponse
    {
        $endpoint = CustomEndpoint::where('slug', $slug)->where('is_active', true)->with('store')->first();
        if (!$endpoint) {
            return response()->json(['success' => false, 'error' => 'Endpoint not found.'], 404);
        }

        $token = $request->header('X-Webhook-Token') ?? $request->query('token');
        if ($endpoint->webhook_token && !hash_equals((string) $endpoint->webhook_token, (string) $token)) {
            return response()->json(['success' => false, 'error' => 'Invalid token.'], 401);
        }

        $store = $endpoint->store;
        $input = $request->all();
        $shopDomain = $store->shopify_store_url ?? '';
        $accessToken = $store->shopify_access_token ?? '';
        $rechargeAccessToken = $store->recharge_access_token ?? '';

        $code = preg_replace('/^<\?php\s*/i', '', trim($endpoint->php_code ?? ''));
        $code = preg_replace('/\?>\s*$/i', '', $code);

        $response = [];
        try {
            eval($code);
        } catch (\Throwable $e) {
            Log::error('CustomEndpoint execute error', ['slug' => $slug, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        return response()->json(array_merge(['success' => true], is_array($response) ? $response : ['data' => $response]));
    }
}
