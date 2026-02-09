<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class CustomEndpointAIService
{
    public function __construct(
        private OpenRouterService $openRouterService
    ) {
    }

    private function getDefaultModel(): string
    {
        return Setting::get('OPENROUTER_DEFAULT_MODEL') ?: config('openrouter.default_model', 'anthropic/claude-3.5-sonnet');
    }

    public function generateInputFields(
        string $prompt,
        array $platforms
    ): array {
        $systemPrompt = "You are an API endpoint designer. Analyze the user's prompt carefully to identify ONLY the input parameters that the user explicitly needs to PROVIDE (receive from the request).

IMPORTANT RULES:
1. Only include input fields that the user MUST PROVIDE as input parameters
2. DO NOT include fields that can be calculated internally (like current date, today's date, timestamps, etc.)
3. DO NOT include fields that are constants or can be derived from other data
4. Focus on what the user explicitly mentions they want to RECEIVE as input
5. If the user says 'I want to receive X', then X is an input field
6. If the user says 'calculate Y' or 'set Y to today', Y is NOT an input field - it's calculated internally

Platforms available: " . implode(', ', $platforms) . "

Examples:
- User says: 'I want to receive subscription_id and update the next charge date to today + 28 days'
  → Input fields: ONLY subscription_id (the date calculation is internal, not an input)
  
- User says: 'I want to receive order_id and add tags'
  → Input fields: ONLY order_id (tags might be hardcoded or calculated)
  
- User says: 'I want to receive order_id and tags'
  → Input fields: order_id AND tags (both are explicitly mentioned as inputs)

Return a JSON object with a 'fields' array. Each field should have:
- name: string (field name, use snake_case)
- type: string (string, number, boolean, array, object)
- required: boolean (true if the user explicitly needs it)
- description: string (what this field is for, based on what the user said)

Return ONLY the fields that the user must provide as input. Be minimal and precise.";

        try {
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ];
            
            $response = $this->openRouterService->chat($messages, $this->getDefaultModel());
            
            // Extract JSON from response
            $content = $response['content'] ?? '{}';

            // Try to extract JSON from markdown code blocks
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                $content = $matches[1];
            } elseif (preg_match('/\{.*\}/s', $content, $matches)) {
                $content = $matches[0];
            }
            
            $parsed = json_decode($content, true);

            return $parsed['fields'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to generate input fields', [
                'error' => $e->getMessage(),
                'prompt' => $prompt,
                'platforms' => $platforms,
            ]);

            return [];
        }
    }

    public function generateCode(
        string $prompt,
        array $platforms,
        array $inputSchema
    ): string {
        $systemPrompt = "You are a PHP developer creating a custom API endpoint for a Laravel application.

Available platforms: " . implode(', ', $platforms) . "

The endpoint will receive input parameters based on this schema:
" . json_encode($inputSchema, JSON_PRETTY_PRINT) . "

Generate PHP code that:
1. Implements the logic described in the user's prompt
2. Uses the provided input parameters from \$input array
3. Can access store data via: \$store (array with store data)
4. Can access Shopify via: \$shopDomain (string) and \$accessToken (string) for API calls
5. Can access Recharge via: \$rechargeAccessToken (string) for API calls
6. Sets \$response = [] array with the result data
7. Uses Laravel's Log facade for logging: use Illuminate\\Support\\Facades\\Log;

Available variables in execution context:
- \$store: array (store data including id, name, etc.)
- \$input: array (input parameters from request)
- \$shopDomain: string (Shopify store domain, e.g. mystore.myshopify.com)
- \$accessToken: string (Shopify access token)
- \$rechargeAccessToken: string (Recharge access token)

For Shopify API calls, use:
- Base URL: https://{\$shopDomain}/admin/api/2024-01/
- Headers: ['X-Shopify-Access-Token' => \$accessToken]

For Recharge API calls, use:
- Base URL: https://api.rechargeapps.com/
- Headers: ['X-Recharge-Access-Token' => \$rechargeAccessToken]

The code should set \$response array with the result. The endpoint will automatically wrap it with 'success' and 'data' keys.

Return ONLY the PHP code, no markdown, no explanations, no function wrapper, just the code that will be executed directly:

// Your code here
\$response = ['message' => 'Success', 'data' => []];";

        try {
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ];
            
            $response = $this->openRouterService->chat($messages, $this->getDefaultModel());
            $content = $response['content'] ?? '';
            
            // Extract code from markdown code blocks if present
            if (preg_match('/```php\s*(.*?)\s*```/s', $content, $matches)) {
                $content = $matches[1];
            } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $matches)) {
                $content = $matches[1];
            }

            return trim($content);
        } catch (\Exception $e) {
            Log::error('Failed to generate code', [
                'error' => $e->getMessage(),
                'prompt' => $prompt,
                'platforms' => $platforms,
            ]);

            return $this->getDefaultCode();
        }
    }

    public function analyzeTestResults(
        string $currentCode,
        array $logs,
        array $testResults
    ): array {
        $systemPrompt = "You are a code analyzer. Analyze the test results, execution logs, and code to provide feedback.

Review:
1. Did the code execute successfully?
2. What does the response data contain?
3. Are there any issues or improvements needed?
4. Is the code doing what was requested in the prompt?

Return a JSON object with:
- success: boolean (whether the code worked correctly)
- analysis: string (detailed analysis of what happened)
- issues: array (list of issues found, if any)
- suggestions: array (suggestions for improvement, if any)
- needs_fix: boolean (whether the code needs to be fixed)";

        $userPrompt = "Code:\n```php\n{$currentCode}\n```\n\n";
        $userPrompt .= "Execution Logs:\n" . json_encode($logs, JSON_PRETTY_PRINT) . "\n\n";
        $userPrompt .= "Test Results:\n" . json_encode($testResults, JSON_PRETTY_PRINT) . "\n\n";
        $userPrompt .= "Analyze these results and provide feedback.";

        try {
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ];
            
            $response = $this->openRouterService->chat($messages, $this->getDefaultModel());
            $content = $response['content'] ?? '{}';
            
            // Try to extract JSON from markdown code blocks
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                $content = $matches[1];
            } elseif (preg_match('/\{.*\}/s', $content, $matches)) {
                $content = $matches[0];
            }
            
            $parsed = json_decode($content, true);
            
            return [
                'success' => $parsed['success'] ?? true,
                'analysis' => $parsed['analysis'] ?? 'Analysis completed',
                'issues' => $parsed['issues'] ?? [],
                'suggestions' => $parsed['suggestions'] ?? [],
                'needs_fix' => $parsed['needs_fix'] ?? false,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to analyze test results', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => true,
                'analysis' => 'Could not analyze results automatically.',
                'issues' => [],
                'suggestions' => [],
                'needs_fix' => false,
            ];
        }
    }

    public function improveCode(
        string $currentCode,
        array $logs,
        array $testResults
    ): string {
        $systemPrompt = "You are a PHP code reviewer and improver. Analyze the provided code, execution logs, and test results to identify issues and improve the code.

The code should:
1. Handle errors gracefully
2. Validate input parameters
3. Use proper error messages
4. Set \$response array with meaningful data
5. Log important steps using \$executionLogs[] array

Available variables:
- \$store: array (store data)
- \$input: array (input parameters)
- \$shopDomain: string (Shopify store domain)
- \$accessToken: string (Shopify access token)
- \$rechargeAccessToken: string (Recharge access token)

Return ONLY the improved PHP code, no markdown, no explanations, no function wrapper, just the code that will be executed directly.";

        $userPrompt = "Current code:\n```php\n{$currentCode}\n```\n\n";
        $userPrompt .= "Execution logs:\n" . json_encode($logs, JSON_PRETTY_PRINT) . "\n\n";
        $userPrompt .= "Test results:\n" . json_encode($testResults, JSON_PRETTY_PRINT) . "\n\n";
        $userPrompt .= "Please improve this code based on the logs and test results. Fix any issues found and ensure the code works correctly.";

        try {
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ];
            
            $response = $this->openRouterService->chat($messages, $this->getDefaultModel());
            $content = $response['content'] ?? '';
            
            // Extract code from markdown code blocks if present
            if (preg_match('/```php\s*(.*?)\s*```/s', $content, $matches)) {
                $content = $matches[1];
            } elseif (preg_match('/```\s*(.*?)\s*```/s', $content, $matches)) {
                $content = $matches[1];
            }

            return trim($content);
        } catch (\Exception $e) {
            Log::error('Failed to improve code', [
                'error' => $e->getMessage(),
                'current_code' => substr($currentCode, 0, 200),
            ]);

            return $currentCode;
        }
    }

    private function getDefaultCode(): string
    {
        return <<<'PHP'
function executeCustomEndpoint(array $input, int $shopId, $shopifyClient = null, $rechargeClient = null): array {
    try {
        // TODO: Implement your custom endpoint logic here
        // Access input parameters via $input array
        // Use $shopifyClient and $rechargeClient for API calls
        
        return [
            'success' => true,
            'data' => [
                'message' => 'Custom endpoint executed successfully',
                'input' => $input,
            ],
        ];
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Custom endpoint error', [
            'error' => $e->getMessage(),
            'shop_id' => $shopId,
        ]);
        
        return [
            'success' => false,
            'data' => [
                'error' => $e->getMessage(),
            ],
        ];
    }
}
PHP;
    }
}
