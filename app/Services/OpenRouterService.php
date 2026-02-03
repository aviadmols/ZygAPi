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
     * Generate PHP rule from order sample and user requirements.
     * Returns array with key 'php_code' (string). Uses prompt template php_rule_generation.
     * @param string $type 'tags', 'metafields', or 'recharge'
     */
    public function generatePhpRule(array $orderData, string $userRequirements, string $type = 'tags'): array
    {
        if ($type === 'metafields') {
            $systemPrompt = "You are an expert at writing PHP code for updating Shopify order metafields in Zyg Automations.

Output **only** PHP code. No JSON, no markdown. Variables: `\$order` (array), `\$shopDomain` (string), `\$accessToken` (string). Set `\$metafields` to an array where keys are namespace.key and values are metafield values.

Structure:
1. \$metafields = [];
2. if (empty(\$order) || empty(\$order['id'])) { return; }
3. Calculate metafield values based on order data
4. Set metafields: \$metafields['custom']['fulfillment_date'] = date('Y-m-d\\TH:i:s\\Z', strtotime(\$order['created_at'] . ' +12 days'));
5. Dates in ISO 8601: YYYY-MM-DDTHH:mm:ssZ

Example: \$metafields['custom']['fulfillment_date'] = date('Y-m-d\\TH:i:s\\Z', strtotime(\$order['created_at'] . ' +12 days'));

Output only PHP code.";
        } elseif ($type === 'recharge') {
            $systemPrompt = "You are an expert at writing PHP code for updating Recharge subscriptions in Zyg Automations.

Output **only** PHP code. No JSON, no markdown. Variables: `\$order` (array), `\$shopDomain` (string), `\$accessToken` (string), `\$rechargeAccessToken` (string). Set `\$subscriptionUpdates` to an array of updates.

Structure:
1. \$subscriptionUpdates = [];
2. if (empty(\$order) || empty(\$order['id'])) { return; }
3. Find subscriptions: GET https://api.rechargeapps.com/subscriptions?shopify_order_id={\$order['id']} with header X-Recharge-Access-Token: \$rechargeAccessToken
4. Calculate updates: \$subscriptionUpdates['next_charge_scheduled_at'] = date('Y-m-d', strtotime('+13 days'));
5. Common fields: next_charge_scheduled_at, next_order_scheduled_at, quantity, order_interval_unit, order_interval_frequency
6. Set commit_update: true

Output only PHP code.";
        } else {
            $systemPrompt = PromptTemplate::getBySlug('php_rule_generation')
                ?? \Database\Seeders\PromptTemplateSeeder::DEFAULT_PHP_RULE_PROMPT;
        }

        $userPrompt = "Order Data (sample):\n" . json_encode($orderData, JSON_PRETTY_PRINT)
            . "\n\nUser requirements:\n" . $userRequirements;

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
2. A detailed description in bullet point format explaining what the rule does

The name should be in English, use title case, and be specific (e.g., 'Subscription Orders with High LTV', 'Box Size Based on Days and Gram', 'Customer Order Count Tagging').

The description should be formatted as bullet points (using • or -), with each action/condition on a separate line. Include:
- What conditions or criteria the rule evaluates
- What tags are applied and when
- Any special logic or considerations

Return ONLY valid JSON in this exact structure (no markdown, no extra text):
{
  \"name\": \"Rule Name Here\",
  \"description\": \"• First action or condition\\n• Second action or condition\\n• Third action or condition\"
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

    /**
     * Generate rule name and description by analyzing the rule code (PHP or JSON).
     * Returns array with 'name' and 'description' keys.
     */
    public function generateRuleNameAndDescriptionFromCode(?string $phpRule = null, ?array $rulesJson = null, ?string $tagsTemplate = null): array
    {
        $systemPrompt = "You are an expert at analyzing Shopify order tagging rule code and creating clear, descriptive names and descriptions.

Given PHP code or JSON rules for a tagging rule, analyze what the code does and generate:
1. A concise, descriptive rule name (max 60 characters) that clearly indicates what the rule does
2. A detailed description in bullet point format explaining what the rule does

The name should be in English, use title case, and be specific (e.g., 'Subscription Orders with High LTV', 'Box Size Based on Days and Gram', 'Customer Order Count Tagging').

The description should be formatted as bullet points (using • or -), with each action/condition on a separate line. Include:
- What conditions or criteria the rule evaluates
- What tags are applied and when
- Any special logic or considerations

Return ONLY valid JSON in this exact structure (no markdown, no extra text):
{
  \"name\": \"Rule Name Here\",
  \"description\": \"• First action or condition\\n• Second action or condition\\n• Third action or condition\"
}";

        $codeDescription = '';
        if ($phpRule) {
            $codeDescription = "PHP Rule Code:\n" . $phpRule;
        } elseif ($rulesJson) {
            $codeDescription = "Rules JSON:\n" . json_encode($rulesJson, JSON_PRETTY_PRINT);
            if ($tagsTemplate) {
                $codeDescription .= "\n\nTags Template:\n" . $tagsTemplate;
            }
        } else {
            // Fallback if no code provided
            return [
                'name' => 'Tagging Rule',
                'description' => 'A tagging rule for Shopify orders.',
            ];
        }

        $userPrompt = "Analyze the following tagging rule code and generate an appropriate name and description:\n\n" . $codeDescription;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        try {
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
                    // Fallback
                    return [
                        'name' => 'Tagging Rule',
                        'description' => 'A tagging rule for Shopify orders.',
                    ];
                }
            }

            $result = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($result['name']) || !isset($result['description'])) {
                // Fallback
                return [
                    'name' => 'Tagging Rule',
                    'description' => 'A tagging rule for Shopify orders.',
                ];
            }

            return [
                'name' => $result['name'],
                'description' => $result['description'],
            ];
        } catch (\Throwable $e) {
            Log::warning('Failed to generate rule name/description from code', ['error' => $e->getMessage()]);
            // Fallback
            return [
                'name' => 'Tagging Rule',
                'description' => 'A tagging rule for Shopify orders.',
            ];
        }
    }
}
