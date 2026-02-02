<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\Store;
use App\Models\TaggingRule;
use App\Services\OpenRouterService;
use Illuminate\Http\Request;
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

            return response()->json([
                'success' => true,
                'message' => $response['content'],
                'usage' => $response['usage'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate rule from conversation.
     * Always returns JSON so the client never gets HTML (e.g. 500 page).
     */
    public function generateRule(Request $request, AiConversation $aiConversation): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_data' => 'required|string',
                'user_requirements' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed: ' . implode(' ', $e->validator->errors()->all()),
            ], 422);
        }

        $orderData = json_decode($validated['order_data'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid JSON in order_data.',
            ], 422);
        }

        try {
            $userRequirements = $validated['user_requirements'];

            // Generate rule using AI
            $ruleData = $this->openRouterService->generateTaggingRule($orderData, $userRequirements);

            // Create tagging rule
            $rule = TaggingRule::create([
                'store_id' => $aiConversation->store_id,
                'name' => 'AI Generated Rule - ' . now()->format('Y-m-d H:i'),
                'description' => 'Automatically generated from AI conversation',
                'rules_json' => $ruleData,
                'tags_template' => $ruleData['tags_template'] ?? null,
                'is_active' => false, // User should review before activating
                'overwrite_existing_tags' => false,
            ]);

            // Link rule to conversation
            $aiConversation->generated_rule_id = $rule->id;
            $aiConversation->save();

            return response()->json([
                'success' => true,
                'rule' => $rule,
                'message' => 'Rule generated successfully',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
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
