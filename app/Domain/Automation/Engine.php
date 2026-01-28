<?php

namespace App\Domain\Automation;

use App\Models\DomainLog;
use App\Models\Run;
use App\Models\RunStep;
use App\Services\CorrelationService;

class Engine
{
    public function __construct(
        private StepRunner $stepRunner,
        private StepResolver $stepResolver,
        private CorrelationService $correlationService
    ) {
    }

    public function execute(Run $run): void
    {
        $run->status = Run::STATUS_RUNNING;
        $run->started_at = now();
        $run->save();

        $automation = $run->automation;
        $context = $this->buildContext($run);

        try {
            $this->correlationService->correlateRun($run, $run->trigger_payload ?? []);

            foreach ($automation->steps as $stepIndex => $stepDefinition) {
                if (!($stepDefinition['enabled'] ?? true)) {
                    continue;
                }

                $resolvedStep = $this->stepResolver->resolveStep($stepDefinition, $context);

                if (isset($resolvedStep['conditions_met']) && !$resolvedStep['conditions_met']) {
                    $this->createRunStep($run, $stepDefinition, 'skipped', null, null, null);
                    continue;
                }

                $stepContext = array_merge($context, $resolvedStep['resolved_inputs'] ?? []);
                $result = $this->stepRunner->runStep($resolvedStep, $stepContext, $run->isDryRun());

                $this->createRunStep(
                    $run,
                    $stepDefinition,
                    $result->isSuccess() ? 'success' : 'failed',
                    $stepContext,
                    $result->data,
                    $result->error,
                    $result->httpRequest,
                    $result->httpResponse,
                    $result->simulationDiff
                );

                if ($result->isError() && !($stepDefinition['continue_on_error'] ?? false)) {
                    $run->status = Run::STATUS_FAILED;
                    $run->finished_at = now();
                    $run->save();
                    return;
                }

                if ($result->isSuccess() && $result->data) {
                    $context = array_merge($context, $result->data);
                }
            }

            $run->status = Run::STATUS_SUCCESS;
        } catch (\Exception $e) {
            $run->status = Run::STATUS_FAILED;
            \Log::error('Automation execution failed', ['error' => $e->getMessage(), 'run_id' => $run->id]);
        } finally {
            $run->finished_at = now();
            $run->save();
        }
    }

    public function createExecutionSnapshot($automation, array $triggerPayload): array
    {
        return [
            'automation_id' => $automation->id,
            'automation_version' => $automation->version,
            'trigger_payload' => $triggerPayload,
            'steps' => $automation->steps,
            'created_at' => now()->toIso8601String(),
        ];
    }

    private function buildContext(Run $run): array
    {
        return array_merge(
            $run->trigger_payload ?? [],
            [
                'shop_id' => $run->shop_id,
                'automation_id' => $run->automation_id,
                'run_id' => $run->id,
            ]
        );
    }

    private function createRunStep(
        Run $run,
        array $stepDefinition,
        string $status,
        ?array $input,
        ?array $output,
        ?string $error,
        ?array $httpRequest = null,
        ?array $httpResponse = null,
        ?array $simulationDiff = null
    ): RunStep {
        $runStep = RunStep::create([
            'run_id' => $run->id,
            'step_id' => $stepDefinition['id'] ?? uniqid(),
            'step_name' => $stepDefinition['name'] ?? 'Unknown',
            'status' => $status,
            'started_at' => now(),
            'finished_at' => now(),
            'input' => $input,
            'output' => $output,
            'error' => $error ? ['message' => $error] : null,
            'http_request' => $httpRequest,
            'http_response' => $httpResponse,
            'simulation_diff' => $simulationDiff,
        ]);

        $this->createDomainLog($run, $runStep, $stepDefinition, $status);

        return $runStep;
    }

    private function createDomainLog(Run $run, RunStep $runStep, array $stepDefinition, string $status): void
    {
        $actionType = $stepDefinition['action_type'] ?? 'unknown';
        $objectType = $this->extractObjectType($actionType);
        $objectExternalId = $run->external_order_id ?? $run->external_subscription_id;

        DomainLog::create([
            'shop_id' => $run->shop_id,
            'object_type' => $objectType,
            'object_external_id' => $objectExternalId,
            'object_display' => $run->order_number ?? $objectExternalId,
            'action_type' => $actionType,
            'run_id' => $run->id,
            'run_step_id' => $runStep->id,
            'status' => $status,
            'message' => $runStep->error['message'] ?? "Step {$runStep->step_name} {$status}",
            'meta' => [
                'step_id' => $runStep->step_id,
                'step_name' => $runStep->step_name,
            ],
        ]);
    }

    private function extractObjectType(string $actionType): string
    {
        if (str_contains($actionType, 'order')) {
            return DomainLog::OBJECT_TYPE_ORDER;
        }
        if (str_contains($actionType, 'subscription')) {
            return DomainLog::OBJECT_TYPE_SUBSCRIPTION;
        }
        return 'unknown';
    }
}
