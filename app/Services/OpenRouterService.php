<?php

namespace App\Services;

use App\Models\IntegrationOpenrouter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    public function testConnection(IntegrationOpenrouter $integration): bool
    {
        try {
            $response = $this->makeRequest($integration, [
                'model' => $integration->default_model,
                'messages' => [
                    ['role' => 'user', 'content' => 'test']
                ],
                'max_tokens' => 10,
            ]);

            return isset($response['choices']);
        } catch (\Exception $e) {
            Log::error('OpenRouter connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function makeRequest(IntegrationOpenrouter $integration, array $data): array
    {
        $response = Http::timeout(config('integrations.openrouter.timeout', 60))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $integration->api_key,
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'Zyg Automation Platform',
            ])
            ->post(config('integrations.openrouter.api_url') . '/chat/completions', $data);

        if (!$response->successful()) {
            throw new \Exception('OpenRouter API error: ' . $response->body());
        }

        return $response->json();
    }

    public function breakDownIntoTasks(string $prompt, IntegrationOpenrouter $integration): array
    {
        $systemPrompt = "You are a task breakdown assistant. Break down the following request into discrete, sequential tasks. Return a JSON array of task objects, each with 'id', 'description', and 'dependencies' (array of task IDs that must complete first).";

        $response = $this->makeRequest($integration, [
            'model' => $integration->default_model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = $response['choices'][0]['message']['content'] ?? '{}';
        $parsed = json_decode($content, true);
        
        return $parsed['tasks'] ?? [];
    }

    public function executeTask(string $taskDescription, array $context, IntegrationOpenrouter $integration): array
    {
        $systemPrompt = "You are an automation assistant. Execute the following task using the provided context. Return a JSON object with 'status' (success/error), 'result', and 'message'.";

        $userPrompt = "Task: {$taskDescription}\n\nContext: " . json_encode($context, JSON_PRETTY_PRINT);

        $response = $this->makeRequest($integration, [
            'model' => $integration->default_model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = $response['choices'][0]['message']['content'] ?? '{}';
        return json_decode($content, true);
    }
}
