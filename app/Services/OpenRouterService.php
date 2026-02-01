<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://openrouter.ai/api/v1';

    public function __construct()
    {
        $this->apiKey = config('openrouter.api_key');
    }

    /**
     * Send chat message to OpenRouter AI
     */
    public function chat(array $messages, string $model = 'anthropic/claude-opus-4.5'): array
    {
        if (!$this->apiKey) {
            throw new \Exception('OpenRouter API key not configured');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'Shopify Tags Management',
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
     * Generate tagging rule from order data and user requirements
     */
    public function generateTaggingRule(array $orderData, string $userRequirements): array
    {
        $systemPrompt = "You are an expert at creating Shopify order tagging rules. 
        You need to analyze order data and user requirements to create a structured tagging rule.
        
        The rule should be returned as JSON with the following structure:
        {
            'conditions': [
                {
                    'field': 'path.to.field',
                    'operator': 'equals|contains|exists|greater_than|less_than',
                    'value': 'expected_value'
                }
            ],
            'tags': [
                'tag1',
                'tag2',
                '{{expression}}' // Can include expressions like {{get(split(...))}}
            ],
            'tags_template': 'Template with expressions like {{switch(...)}}'
        }
        
        Support these functions in tag expressions:
        - get(array, index) - Get element from array
        - split(string, delimiter) - Split string into array
        - switch(value, case1, result1, case2, result2, ..., default) - Switch statement
        
        Example tag template:
        {{switch(12.Days + \"-\" + 12.Gram; \"14D-50\"; \"A\"; \"14D-75\"; \"A\"; \"Unknown\")}}";

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
}
