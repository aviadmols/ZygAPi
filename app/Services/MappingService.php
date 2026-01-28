<?php

namespace App\Services;

class MappingService
{
    public function resolveInputMap(array $step, array $context): array
    {
        if (!isset($step['input_map']) || !is_array($step['input_map'])) {
            return [];
        }

        $resolved = [];
        foreach ($step['input_map'] as $targetKey => $sourcePath) {
            $resolved[$targetKey] = $this->extractValue($context, $sourcePath);
        }
        return $resolved;
    }

    public function extractValue(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    public function applyTemplate(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{(\w+(?:\.\w+)*)\}\}/', function ($matches) use ($context) {
            $path = $matches[1];
            $value = $this->extractValue($context, $path);
            return $value !== null ? (string) $value : $matches[0];
        }, $template);
    }
}
