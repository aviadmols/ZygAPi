<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class OpenRouterSettingsController extends Controller
{
    public const KEY_API_KEY = 'OPENROUTER_API_KEY';
    public const KEY_DEFAULT_MODEL = 'OPENROUTER_DEFAULT_MODEL';

    /**
     * Popular OpenRouter models for dropdown.
     */
    public static function availableModels(): array
    {
        return [
            'anthropic/claude-3.5-sonnet' => 'Claude 3.5 Sonnet (Anthropic)',
            'anthropic/claude-3-opus' => 'Claude 3 Opus (Anthropic)',
            'anthropic/claude-3-haiku' => 'Claude 3 Haiku (Anthropic)',
            'openai/gpt-4o' => 'GPT-4o (OpenAI)',
            'openai/gpt-4o-mini' => 'GPT-4o Mini (OpenAI)',
            'google/gemini-pro-1.5' => 'Gemini Pro 1.5 (Google)',
            'google/gemini-flash-1.5' => 'Gemini Flash 1.5 (Google)',
            'meta-llama/llama-3.1-70b-instruct' => 'Llama 3.1 70B (Meta)',
            'mistralai/mistral-large' => 'Mistral Large',
        ];
    }

    /**
     * Show OpenRouter settings form.
     */
    public function index(): View
    {
        $apiKey = Setting::get(self::KEY_API_KEY) ?: config('openrouter.api_key');
        $currentModel = Setting::get(self::KEY_DEFAULT_MODEL) ?: config('openrouter.default_model', 'anthropic/claude-3.5-sonnet');
        $models = self::availableModels();
        if ($currentModel && !isset($models[$currentModel])) {
            $models = [$currentModel => $currentModel] + $models;
        }

        $apiKeyMasked = $apiKey && strlen($apiKey) > 12
            ? substr($apiKey, 0, 8) . '…' . substr($apiKey, -4)
            : ($apiKey ? '••••••••' : '');

        return view('settings.openrouter', [
            'hasApiKey' => !empty($apiKey),
            'apiKeyMasked' => $apiKeyMasked,
            'currentModel' => $currentModel,
            'models' => $models,
        ]);
    }

    /**
     * Update OpenRouter settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'openrouter_api_key' => 'nullable|string|max:500',
            'openrouter_default_model' => 'required|string|max:255',
        ]);

        if (!empty($validated['openrouter_api_key'])) {
            Setting::set(self::KEY_API_KEY, $validated['openrouter_api_key']);
        }

        Setting::set(self::KEY_DEFAULT_MODEL, $validated['openrouter_default_model']);

        return redirect()->route('settings.openrouter.index')
            ->with('success', 'OpenRouter settings saved successfully');
    }
}
