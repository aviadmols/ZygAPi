<?php

namespace App\Services;

class TemplateService
{
    public function render(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{(\w+(?:\.\w+)*)\}\}/', function ($matches) use ($context) {
            $path = $matches[1];
            $mappingService = app(MappingService::class);
            $value = $mappingService->extractValue($context, $path);
            return $value !== null ? (string) $value : $matches[0];
        }, $template);
    }

    public function validateTemplate(string $template): bool
    {
        $pattern = '/\{\{(\w+(?:\.\w+)*)\}\}/';
        return preg_match($pattern, $template) !== false;
    }
}
