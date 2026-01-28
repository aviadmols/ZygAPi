<?php

namespace App\Domain\Automation;

use App\Services\MappingService;

class StepResolver
{
    public function __construct(
        private MappingService $mappingService
    ) {
    }

    public function resolveStep(array $stepDefinition, array $context): array
    {
        $resolved = $stepDefinition;

        if (isset($stepDefinition['input_map'])) {
            $resolved['resolved_inputs'] = $this->mappingService->resolveInputMap(
                $stepDefinition,
                $context
            );
        }

        if (isset($stepDefinition['conditions'])) {
            $resolved['conditions_met'] = $this->evaluateConditions(
                $stepDefinition['conditions'],
                $context
            );
        }

        return $resolved;
    }

    public function evaluateConditions(array $conditions, array $context): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            if (!$field) {
                continue;
            }

            $fieldValue = $this->mappingService->extractValue($context, $field);

            if (!$this->evaluateCondition($fieldValue, $operator, $value)) {
                return false;
            }
        }

        return true;
    }

    public function resolveInputs(array $inputMap, array $context): array
    {
        return $this->mappingService->resolveInputMap(
            ['input_map' => $inputMap],
            $context
        );
    }

    private function evaluateCondition($fieldValue, string $operator, $expectedValue): bool
    {
        return match ($operator) {
            'equals' => $fieldValue === $expectedValue,
            'not_equals' => $fieldValue !== $expectedValue,
            'contains' => str_contains((string) $fieldValue, (string) $expectedValue),
            'greater_than' => $fieldValue > $expectedValue,
            'less_than' => $fieldValue < $expectedValue,
            'exists' => $fieldValue !== null,
            'not_exists' => $fieldValue === null,
            default => false,
        };
    }
}
