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

    /**
     * Generate PHP code for a custom endpoint (Shopify or Recharge).
     * Uses platform docs: fetch input params, execute prompt logic, return test_return_values structure.
     * Returns array with 'php_code' and optionally 'http_method' (POST/PUT).
     */
    public function generateCustomEndpointCode(string $platform, string $prompt, array $inputParams, array $testReturnValues): array
    {
        $platform = strtolower($platform) === 'recharge' ? 'recharge' : 'shopify';
        $inputList = array_map(function ($p) {
            return is_array($p) ? ($p['name'] ?? $p) : $p;
        }, $inputParams);
        $returnList = array_map(function ($r) {
            return is_array($r) ? ($r['name'] ?? $r) : $r;
        }, $testReturnValues);

        $systemPrompt = "You are an expert at writing PHP code for API endpoints. You will receive:
1. Platform: {$platform} (use {$platform} API documentation)
2. A prompt describing what the endpoint should do
3. Input parameters the endpoint receives (from request body/query)
4. Expected return keys (the endpoint must set/output these for the response)

Rules:
- Output ONLY PHP code. No markdown, no explanation. The code will run in a context where \$store (Store model), \$input (array of request params), \$shopDomain, \$accessToken (Shopify), and \$rechargeAccessToken (if Recharge) are available.
- For Shopify: use \$shopDomain and \$accessToken for Admin API calls (GET/POST/PUT). Base URL: https://{\$shopDomain}/admin/api/2024-01/
- For Recharge: use \$rechargeAccessToken. Base URL: https://api.rechargeapps.com/
- Fetch data from {$platform} based on \$input (e.g. order_id -> GET order, subscription_id -> GET subscription).
- Implement the logic described in the prompt.
- Set a variable \$response = [] with the expected return keys. The endpoint will JSON-encode \$response.
- IMPORTANT: If the expected return includes 'updated' (or similar), set it to true ONLY when the update API call actually succeeded (e.g. HTTP 2xx). If the API call failed or returned an error, set updated to false and include any error info in the response so the caller knows the update did not happen.
- Use PUT when updating existing resources, POST when creating or when the docs say POST. Set \$httpMethod = 'POST' or 'PUT' at the end of your code so the endpoint knows which method to document.
- Do not use <?php or exit or echo. You may use return; to exit early.";

        $userPrompt = "Platform: {$platform}\n\nPrompt:\n{$prompt}\n\nInput parameters (the endpoint receives these):\n" . implode(", ", $inputList)
            . "\n\nExpected return keys (response must include these):\n" . implode(", ", $returnList);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        $response = $this->chat($messages);
        $content = trim($response['content']);

        if (preg_match('/```(?:php)?\s*(.*?)\s*```/s', $content, $matches)) {
            $content = trim($matches[1]);
        }

        $httpMethod = 'POST';
        if (preg_match('/\$httpMethod\s*=\s*[\'"](PUT|POST)[\'"]/i', $content, $m)) {
            $httpMethod = strtoupper($m[1]);
        }

        return ['php_code' => $content, 'http_method' => $httpMethod];
    }
}
