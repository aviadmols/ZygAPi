<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\TaggingRule;
use App\Services\ShopifyService;
use App\Services\TaggingEngineService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class TaggingRuleController extends Controller
{
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
    public function create(): View
    {
        $stores = Store::where('is_active', true)->get();
        return view('tagging-rules.create', compact('stores'));
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
            'rules_json' => 'nullable|json',
            'tags_template' => 'nullable|string',
            'is_active' => 'boolean',
            'overwrite_existing_tags' => 'boolean',
        ]);

        TaggingRule::create($validated);

        return redirect()->route('tagging-rules.index')
            ->with('success', 'חוקיות נוצרה בהצלחה');
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
    public function edit(TaggingRule $taggingRule): View
    {
        $stores = Store::where('is_active', true)->get();
        return view('tagging-rules.edit', compact('taggingRule', 'stores'));
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
            'rules_json' => 'nullable|json',
            'tags_template' => 'nullable|string',
            'is_active' => 'boolean',
            'overwrite_existing_tags' => 'boolean',
        ]);

        $taggingRule->update($validated);

        return redirect()->route('tagging-rules.index')
            ->with('success', 'חוקיות עודכנה בהצלחה');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TaggingRule $taggingRule): RedirectResponse
    {
        $taggingRule->delete();

        return redirect()->route('tagging-rules.index')
            ->with('success', 'חוקיות נמחקה בהצלחה');
    }

    /**
     * Test rule on a specific order
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
