<?php

namespace App\Services;

use App\Models\PromptTemplate;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://openrouter.ai/api/v1';

    public function __construct()
    {
        $this->apiKey = (string) (Setting::get('OPENROUTER_API_KEY') ?: config('openrouter.api_key'));
    }

    /**
     * Send chat message to OpenRouter AI
     */
    public function chat(array $messages, ?string $model = null): array
    {
        if (!$this->apiKey) {
            throw new \Exception('OpenRouter API key not configured. Set it in Settings → OpenRouter.');
        }

        $model = $model ?? Setting::get('OPENROUTER_DEFAULT_MODEL') ?: config('openrouter.default_model', 'anthropic/claude-3.5-sonnet');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'Zyg AutoTag',
            ])->post("{$this->baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => $messages,
            ]);

            if ($response->failed()) {
                Log::error("OpenRouter API Error: HTTP {$response->status()} - " . $response->body());
                throw new \Exception("OpenRouter API Error: " . $response->body());
            }

            $data = $response->json();

            if (!isset($data['choices'][0]['message']['content'])) {
                throw new \Exception("Invalid response from OpenRouter API");
            }

            return [
                'content' => $data['choices'][0]['message']['content'],
                'usage' => $data['usage'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error("OpenRouter API Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate tagging rule from order data and user requirements.
     * Uses system prompt from DB (Prompt Management) or built-in default.
     */
    public function generateTaggingRule(array $orderData, string $userRequirements): array
    {
        $systemPrompt = PromptTemplate::getBySlug('tagging_rule_generation')
            ?? $this->getDefaultTaggingPrompt();

        $userPrompt = "Order Data:\n" . json_encode($orderData, JSON_PRETTY_PRINT) . "\n\nUser Requirements:\n" . $userRequirements;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $response = $this->chat($messages);

        // Try to extract JSON from response
        $content = $response['content'];
        
        // Look for JSON code block
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $jsonContent = $matches[1];
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $matches)) {
            $jsonContent = $matches[1];
        } else {
            // Try to find JSON object in the text
            if (preg_match('/\{.*\}/s', $content, $matches)) {
                $jsonContent = $matches[0];
            } else {
                throw new \Exception("Could not extract JSON from AI response");
            }
        }

        $rule = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in AI response: " . json_last_error_msg());
        }

        return $rule;
    }

    /**
     * Built-in default prompt when none is stored in DB.
     */
    protected function getDefaultTaggingPrompt(): string
    {
        return \Database\Seeders\PromptTemplateSeeder::DEFAULT_TAGGING_PROMPT;
    }
}
