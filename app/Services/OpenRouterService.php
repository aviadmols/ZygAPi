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
                'X-Title' => 'Zyg Automations',
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

    /**
     * Generate PHP tagging rule from order sample and user requirements.
     * Returns array with key 'php_code' (string). Uses prompt template php_rule_generation.
     */
    public function generatePhpRule(array $orderData, string $userRequirements): array
    {
        $systemPrompt = PromptTemplate::getBySlug('php_rule_generation')
            ?? \Database\Seeders\PromptTemplateSeeder::DEFAULT_PHP_RULE_PROMPT;

        $userPrompt = "Order Data (sample):\n" . json_encode($orderData, JSON_PRETTY_PRINT)
            . "\n\nUser requirements (what to check and which tags to return):\n" . $userRequirements;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $response = $this->chat($messages);
        $content = trim($response['content']);

        // Strip markdown code block if present
        if (preg_match('/```(?:php)?\s*(.*?)\s*```/s', $content, $matches)) {
            $content = trim($matches[1]);
        }

        return ['php_code' => $content];
    }

    /**
     * Generate rule name and description from user requirements using AI.
     * Returns array with 'name' and 'description' keys.
     */
    public function generateRuleNameAndDescription(string $userRequirements, ?array $orderData = null): array
    {
        $systemPrompt = "You are an expert at creating clear, descriptive names and descriptions for Shopify order tagging rules.

Given the user's requirements for a tagging rule, generate:
1. A concise, descriptive rule name (max 60 characters) that clearly indicates what the rule does
2. A detailed description (2-4 sentences) explaining what conditions the rule checks and what tags it applies

The name should be in English, use title case, and be specific (e.g., 'Subscription Orders with High LTV', 'Box Size Based on Days and Gram', 'Customer Order Count Tagging').

The description should explain:
- What conditions or criteria the rule evaluates
- What tags are applied and when
- Any special logic or considerations

Return ONLY valid JSON in this exact structure (no markdown, no extra text):
{
  \"name\": \"Rule Name Here\",
  \"description\": \"Detailed description of what the rule does, what conditions it checks, and what tags it applies.\"
}";

        $userPrompt = "User Requirements:\n" . $userRequirements;
        if ($orderData) {
            $userPrompt .= "\n\nSample Order Data:\n" . json_encode($orderData, JSON_PRETTY_PRINT);
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $response = $this->chat($messages);
        $content = trim($response['content']);

        // Try to extract JSON from response
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $jsonContent = $matches[1];
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $matches)) {
            $jsonContent = $matches[1];
        } else {
            // Try to find JSON object in the text
            if (preg_match('/\{.*\}/s', $content, $matches)) {
                $jsonContent = $matches[0];
            } else {
                // Fallback: create simple name and description
                return [
                    'name' => 'AI Generated Rule - ' . now()->format('Y-m-d H:i'),
                    'description' => 'Automatically generated from AI conversation: ' . substr($userRequirements, 0, 200),
                ];
            }
        }

        $result = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($result['name']) || !isset($result['description'])) {
            // Fallback: create simple name and description
            return [
                'name' => 'AI Generated Rule - ' . now()->format('Y-m-d H:i'),
                'description' => 'Automatically generated from AI conversation: ' . substr($userRequirements, 0, 200),
            ];
        }

        return [
            'name' => $result['name'],
            'description' => $result['description'],
        ];
    }
}
