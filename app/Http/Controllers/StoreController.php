<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $stores = Store::latest()->paginate(15);
        return view('stores.index', compact('stores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('stores.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shopify_store_url' => 'required|string|max:255',
            'shopify_access_token' => 'required|string',
            'recharge_access_token' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Store::create($validated);

        return redirect()->route('stores.index')
            ->with('success', 'Store created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store): View
    {
        return view('stores.show', compact('store'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store): View
    {
        return view('stores.edit', compact('store'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'shopify_store_url' => 'required|string|max:255',
            'shopify_access_token' => 'required|string',
            'recharge_access_token' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $store->update($validated);

        return redirect()->route('stores.index')
            ->with('success', 'Store updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store): RedirectResponse
    {
        $store->delete();

        return redirect()->route('stores.index')
            ->with('success', 'Store deleted successfully');
    }
}
