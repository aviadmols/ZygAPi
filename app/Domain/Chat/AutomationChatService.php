<?php

namespace App\Domain\Chat;

use App\Models\Automation;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Run;
use App\Services\OpenRouterService;
use App\Models\IntegrationOpenrouter;

class AutomationChatService
{
    public function __construct(
        private OpenRouterService $openRouterService
    ) {
    }

    public function analyzePayload(int $shopId, int $automationId, array $payload): ChatSession
    {
        $session = ChatSession::create([
            'shop_id' => $shopId,
            'automation_id' => $automationId,
            'title' => 'Payload Analysis - ' . now()->format('Y-m-d H:i'),
        ]);

        $automation = Automation::findOrFail($automationId);
        $integration = IntegrationOpenrouter::where('shop_id', $shopId)->firstOrFail();

        $userMessage = ChatMessage::create([
            'chat_session_id' => $session->id,
            'role' => ChatMessage::ROLE_USER,
            'content' => json_encode($payload, JSON_PRETTY_PRINT),
        ]);

        $lastRun = Run::where('automation_id', $automationId)
            ->where('status', Run::STATUS_FAILED)
            ->latest()
            ->first();

        $patch = $this->generatePatchSuggestion($session, $payload, $lastRun, $automation, $integration);

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'role' => ChatMessage::ROLE_ASSISTANT,
            'content' => json_encode($patch, JSON_PRETTY_PRINT),
            'metadata' => ['type' => 'patch_suggestion'],
        ]);

        return $session;
    }

    public function generatePatchSuggestion(
        ChatSession $session,
        array $payload,
        ?Run $lastRun,
        Automation $automation,
        IntegrationOpenrouter $integration
    ): array {
        $prompt = $this->buildAnalysisPrompt($automation, $payload, $lastRun);

        $tasks = $this->openRouterService->breakDownIntoTasks($prompt, $integration);

        $results = [];
        foreach ($tasks as $task) {
            $context = [
                'automation' => $automation->toArray(),
                'payload' => $payload,
                'last_run' => $lastRun ? $lastRun->toArray() : null,
            ];

            $result = $this->openRouterService->executeTask($task['description'], $context, $integration);
            $results[] = $result;
        }

        $patchBuilder = app(PatchSuggestionBuilder::class);
        return $patchBuilder->buildPatch($automation, $payload, $results);
    }

    private function buildAnalysisPrompt(Automation $automation, array $payload, ?Run $lastRun): string
    {
        $prompt = "Analyze this automation and payload:\n\n";
        $prompt .= "Automation Steps: " . json_encode($automation->steps, JSON_PRETTY_PRINT) . "\n\n";
        $prompt .= "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

        if ($lastRun) {
            $prompt .= "Last Run Errors: " . json_encode($lastRun->steps()->where('status', 'failed')->get()->toArray(), JSON_PRETTY_PRINT) . "\n\n";
        }

        $prompt .= "Identify missing input mappings, incorrect conditions, or other issues. Suggest improvements.";

        return $prompt;
    }
}
