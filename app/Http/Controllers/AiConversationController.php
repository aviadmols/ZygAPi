<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
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
use Illuminate\Validation\ValidationException;

class AiConversationController extends Controller
{
    protected OpenRouterService $openRouterService;

    public function __construct(OpenRouterService $openRouterService)
    {
        $this->openRouterService = $openRouterService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $conversations = AiConversation::with('store', 'user', 'generatedRule')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('ai-conversations.index', compact('conversations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $stores = Store::where('is_active', true)->get();
        return view('ai-conversations.create', compact('stores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
        ]);

        $conversation = AiConversation::create([
            'store_id' => $validated['store_id'],
            'user_id' => auth()->id(),
            'messages' => [],
        ]);

        return redirect()->route('ai-conversations.show', $conversation)
            ->with('success', 'New conversation created');
    }

    /**
     * Display the specified resource.
     */
    public function show(AiConversation $aiConversation): View
    {
        $aiConversation->load('store', 'user', 'generatedRule');
        return view('ai-conversations.show', compact('aiConversation'));
    }

    /**
     * Send message to AI
     */
    public function chat(Request $request, AiConversation $aiConversation): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'order_data' => 'nullable|json',
        ]);

        Log::info('[AI Conversation] CHAT request', [
            'conversation_id' => $aiConversation->id,
            'message_length' => strlen($validated['message']),
            'has_order_data' => !empty($validated['order_data']),
        ]);

        $messages = $aiConversation->messages ?? [];
        
        // Add user message
        $messages[] = [
            'role' => 'user',
            'content' => $validated['message'],
            'timestamp' => now()->toDateTimeString(),
        ];

        try {
            // Add system message if this is the first message
            if (empty($messages) || count($messages) === 1) {
                $systemMessage = [
                    'role' => 'system',
                    'content' => 'You are a helper for creating Shopify order tagging rules. The user will define rules in text and you will help create an appropriate JSON structure.',
                ];
                array_unshift($messages, $systemMessage);
            }

            // Get AI response
            $response = $this->openRouterService->chat($messages);

            // Add AI response
            $messages[] = [
                'role' => 'assistant',
                'content' => $response['content'],
                'timestamp' => now()->toDateTimeString(),
            ];

            // Update conversation
            $aiConversation->messages = $messages;
            $aiConversation->save();

            Log::info('[AI Conversation] CHAT response', [
                'conversation_id' => $aiConversation->id,
                'success' => true,
                'response_length' => strlen($response['content'] ?? ''),
                'usage' => $response['usage'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => $response['content'],
                'usage' => $response['usage'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::warning('[AI Conversation] CHAT response ERROR', [
                'conversation_id' => $aiConversation->id,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test with order number: generate PHP from conversation, run on order, return tags.
     */
    public function testOrder(Request $request, AiConversation $aiConversation): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|string',
                'order_data' => 'nullable|string',
                'php_code' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            Log::warning('[AI Conversation] TEST_ORDER validation failed', ['conversation_id' => $aiConversation->id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . implode(' ', $e->validator->errors()->all()),
            ], 422);
        }

        Log::info('[AI Conversation] TEST_ORDER request', [
            'conversation_id' => $aiConversation->id,
            'order_id' => $validated['order_id'],
            'has_order_data' => !empty($validated['order_data']),
            'has_php_code' => !empty(trim($validated['php_code'] ?? '')),
        ]);

        try {
            $store = $aiConversation->store;
            $shopifyService = new ShopifyService($store);
            $order = $shopifyService->getOrderByIdOrNumber($validated['order_id']);
            Log::info('[AI Conversation] TEST_ORDER step: order fetched', ['order_id' => $order['id'] ?? $validated['order_id']]);

            $phpCode = trim($validated['php_code'] ?? '');
            if ($phpCode === '') {
                $userRequirements = $this->getUserRequirementsFromConversation($aiConversation);
                if (empty($userRequirements)) {
                    Log::warning('[AI Conversation] TEST_ORDER: no user requirements');
                    return response()->json([
                        'success' => false,
                        'error' => 'No user requirements in conversation. Send at least one message describing what to check and which tags to return, or paste PHP code in the PHP Rule field.',
                    ], 422);
                }
                Log::info('[AI Conversation] TEST_ORDER step: user_requirements length', ['length' => strlen($userRequirements)]);

                $orderSample = $order;
                if (!empty($validated['order_data'])) {
                    $decoded = json_decode($validated['order_data'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $orderSample = $decoded;
                    }
                }

                $result = $this->openRouterService->generatePhpRule($orderSample, $userRequirements);
                $phpCode = $result['php_code'];
                Log::info('[AI Conversation] TEST_ORDER step: PHP generated', ['php_code_length' => strlen($phpCode)]);
            } else {
                Log::info('[AI Conversation] TEST_ORDER step: using provided php_code', ['php_code_length' => strlen($phpCode)]);
            }

            $taggingEngine = new TaggingEngineService();
            $tags = $taggingEngine->executePhpRule($phpCode, $order, $store);
            Log::info('[AI Conversation] TEST_ORDER response', [
                'conversation_id' => $aiConversation->id,
                'success' => true,
                'tags_count' => count($tags),
                'tags' => $tags,
            ]);

            return response()->json([
                'success' => true,
                'tags' => $tags,
                'php_code' => $phpCode,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[AI Conversation] TEST_ORDER response ERROR', [
                'conversation_id' => $aiConversation->id,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate rule from conversation: produce PHP and save as tagging rule (php_rule).
     */
    public function generateRule(Request $request, AiConversation $aiConversation): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_data' => 'nullable|string',
                'order_id' => 'nullable|string',
                'user_requirements' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . implode(' ', $e->validator->errors()->all()),
            ], 422);
        }

        $orderData = null;
        if (!empty($validated['order_data'])) {
            $orderData = json_decode($validated['order_data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid JSON in order_data.',
                ], 422);
            }
        }
        if ($orderData === null && !empty($validated['order_id'])) {
            try {
                $store = $aiConversation->store;
                $shopifyService = new ShopifyService($store);
                $orderData = $shopifyService->getOrderByIdOrNumber($validated['order_id']);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Could not fetch order: ' . $e->getMessage(),
                ], 422);
            }
        }
        if ($orderData === null) {
            return response()->json([
                'success' => false,
                'error' => 'Provide sample order: paste Order JSON or enter Order number to fetch.',
            ], 422);
        }

        Log::info('[AI Conversation] GENERATE_RULE request', [
            'conversation_id' => $aiConversation->id,
            'has_order_data' => !empty($validated['order_data']),
            'order_id' => $validated['order_id'] ?? null,
            'user_requirements_length' => strlen($validated['user_requirements'] ?? ''),
        ]);

        try {
            $userRequirements = $validated['user_requirements'];

            // Generate PHP rule using AI
            $result = $this->openRouterService->generatePhpRule($orderData, $userRequirements);
            $phpCode = $result['php_code'];
            Log::info('[AI Conversation] GENERATE_RULE step: PHP generated', ['php_code_length' => strlen($phpCode)]);

            // Create tagging rule with php_rule (usable in tagging-rules)
            $rule = TaggingRule::create([
                'store_id' => $aiConversation->store_id,
                'name' => 'AI Generated Rule - ' . now()->format('Y-m-d H:i'),
                'description' => 'Automatically generated from AI conversation (PHP rule)',
                'rules_json' => null,
                'tags_template' => null,
                'php_rule' => $phpCode,
                'is_active' => false,
                'overwrite_existing_tags' => false,
            ]);

            $aiConversation->generated_rule_id = $rule->id;
            $aiConversation->save();

            Log::info('[AI Conversation] GENERATE_RULE response', [
                'conversation_id' => $aiConversation->id,
                'success' => true,
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
            ]);

            return response()->json([
                'success' => true,
                'rule' => $rule,
                'php_code' => $phpCode,
                'message' => 'Rule generated successfully. You can edit and test it in Tagging Rules.',
            ]);
        } catch (\Throwable $e) {
            Log::warning('[AI Conversation] GENERATE_RULE response ERROR', [
                'conversation_id' => $aiConversation->id,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user requirements string from conversation messages (user role only).
     */
    protected function getUserRequirementsFromConversation(AiConversation $aiConversation): string
    {
        $messages = $aiConversation->messages ?? [];
        $parts = [];
        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') === 'user' && !empty($msg['content'])) {
                $parts[] = trim($msg['content']);
            }
        }
        return implode("\n", $parts);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AiConversation $aiConversation): RedirectResponse
    {
        $aiConversation->delete();

        return redirect()->route('ai-conversations.index')
            ->with('success', 'Conversation deleted successfully');
    }
}
