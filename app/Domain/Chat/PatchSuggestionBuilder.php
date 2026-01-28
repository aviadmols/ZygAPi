<?php

namespace App\Domain\Chat;

use App\Models\Automation;

class PatchSuggestionBuilder
{
    public function buildPatch(array $automation, array $payload, array $logs): array
    {
        $patches = [];

        $missingMappings = $this->detectMissingMappings($automation, $payload);
        if (!empty($missingMappings)) {
            $patches[] = [
                'op' => 'add',
                'path' => '/steps/0/input_map',
                'value' => $missingMappings,
            ];
        }

        $stepImprovements = $this->suggestStepImprovements($automation['steps'] ?? [], $logs);
        foreach ($stepImprovements as $improvement) {
            $patches[] = $improvement;
        }

        return [
            'patch_format' => 'json-patch',
            'patches' => $patches,
            'explanation' => 'Suggested improvements based on payload analysis',
            'risk_flags' => $this->identifyRiskFlags($patches),
        ];
    }

    public function detectMissingMappings(array $automation, array $payload): array
    {
        $mappings = [];
        $payloadKeys = $this->flattenArray($payload);

        foreach ($automation['steps'] ?? [] as $step) {
            $requiredInputs = $step['config']['required_fields'] ?? [];
            foreach ($requiredInputs as $field) {
                if (!isset($step['input_map'][$field]) && isset($payloadKeys[$field])) {
                    $mappings[$field] = "trigger_payload.{$field}";
                }
            }
        }

        return $mappings;
    }

    public function suggestStepImprovements(array $steps, array $logs): array
    {
        $improvements = [];

        foreach ($logs as $log) {
            if (isset($log['status']) && $log['status'] === 'error') {
                $improvements[] = [
                    'op' => 'replace',
                    'path' => '/steps/0/retry_policy',
                    'value' => [
                        'max_attempts' => 3,
                        'backoff_seconds' => 5,
                    ],
                ];
            }
        }

        return $improvements;
    }

    private function identifyRiskFlags(array $patches): array
    {
        $flags = [];

        foreach ($patches as $patch) {
            if ($patch['op'] === 'add' && str_contains($patch['path'], 'write')) {
                $flags[] = 'write_operation';
            }
            if (!isset($patch['value']['id'])) {
                $flags[] = 'missing_id';
            }
        }

        return $flags;
    }

    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }
}
