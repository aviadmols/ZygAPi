<?php

namespace App\Http\Controllers;

use App\Models\PromptTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PromptTemplateController extends Controller
{
    /**
     * List all prompt templates.
     */
    public function index(): View
    {
        $prompts = PromptTemplate::orderBy('slug')->get();

        return view('prompt-templates.index', compact('prompts'));
    }

    /**
     * Edit a prompt template.
     */
    public function edit(PromptTemplate $promptTemplate): View
    {
        return view('prompt-templates.edit', compact('promptTemplate'));
    }

    /**
     * Update a prompt template.
     */
    public function update(Request $request, PromptTemplate $promptTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string|max:500',
        ]);

        $promptTemplate->update($validated);

        return redirect()->route('prompt-templates.index')
            ->with('success', 'Prompt updated successfully');
    }
}
