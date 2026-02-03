<?php

namespace App\Services;

use App\Models\Store;
use App\Models\TaggingRule;
use Illuminate\Support\Facades\Log;

class TaggingEngineService
{
    /**
     * Evaluate expression with order data
     * Supports: {{get(split(...))}}, {{switch(...)}}, {{field.path}}
     */
    public function evaluateExpression(string $expression, array $orderData): string
    {
        // Remove {{ and }}
        $expression = trim($expression, '{}');

        // Handle nested function calls
        return $this->parseExpression($expression, $orderData);
    }

    /**
     * Parse expression recursively
     */
    protected function parseExpression(string $expression, array $orderData): string
    {
        // Handle switch statements
        if (preg_match('/^switch\s*\((.*)\)$/i', $expression, $matches)) {
            return $this->evaluateSwitch($matches[1], $orderData);
        }

        // Handle get function
        if (preg_match('/^get\s*\((.*)\)$/i', $expression, $matches)) {
            return $this->evaluateGet($matches[1], $orderData);
        }

        // Handle split function
        if (preg_match('/^split\s*\((.*)\)$/i', $expression, $matches)) {
            return $this->evaluateSplit($matches[1], $orderData);
        }

        // Handle field access (e.g., 12.Days, order.line_items[0].sku)
        if (preg_match('/^(\d+\.\w+)$/', $expression, $matches)) {
            return $this->getFieldValue($matches[1], $orderData);
        }

        // Handle string concatenation with +
        if (strpos($expression, '+') !== false) {
            $parts = explode('+', $expression);
            $result = '';
            foreach ($parts as $part) {
                $part = trim($part, ' "\'');
                // Check if it's a field reference or a literal string
                if (preg_match('/^(\d+\.\w+)$/', $part)) {
                    $result .= $this->getFieldValue($part, $orderData);
                } else {
                    $result .= $part;
                }
            }
            return $result;
        }

        // Try to get field value directly
        return $this->getFieldValue($expression, $orderData);
    }

    /**
     * Evaluate switch statement
     * Format: switch(value; "case1"; "result1"; "case2"; "result2"; ...; "default")
     */
    protected function evaluateSwitch(string $switchExpr, array $orderData): string
    {
        // Split by semicolon
        $parts = array_map('trim', explode(';', $switchExpr));
        
        if (count($parts) < 2) {
            return '';
        }

        // First part is the value to compare
        $value = $this->parseExpression($parts[0], $orderData);

        // Remaining parts are pairs of case and result, last one is default
        for ($i = 1; $i < count($parts) - 1; $i += 2) {
            $case = trim($parts[$i], ' "\'');
            $result = trim($parts[$i + 1], ' "\'');

            if ($value === $case) {
                return $result;
            }
        }

        // Return default (last part)
        return trim(end($parts), ' "\'');
    }

    /**
     * Evaluate get function
     * Format: get(array, index)
     */
    protected function evaluateGet(string $getExpr, array $orderData): string
    {
        // Split by comma
        $parts = array_map('trim', explode(',', $getExpr));
        
        if (count($parts) < 2) {
            return '';
        }

        $arrayExpr = trim($parts[0]);
        $index = intval(trim($parts[1]));

        // Evaluate array expression (might be a split result or field)
        $array = $this->parseExpression($arrayExpr, $orderData);

        // If array is a string representation, try to decode it
        if (is_string($array)) {
            // Check if it's JSON
            $decoded = json_decode($array, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $array = $decoded;
            } else {
                // Might be comma-separated
                $array = explode(',', $array);
            }
        }

        if (is_array($array) && isset($array[$index])) {
            return (string) $array[$index];
        }

        return '';
    }

    /**
     * Evaluate split function
     * Format: split(string, delimiter)
     */
    protected function evaluateSplit(string $splitExpr, array $orderData): array
    {
        // Split by comma
        $parts = array_map('trim', explode(',', $splitExpr));
        
        if (count($parts) < 2) {
            return [];
        }

        $stringExpr = trim($parts[0]);
        $delimiter = trim($parts[1], ' "\';');

        // Evaluate string expression
        $string = $this->parseExpression($stringExpr, $orderData);

        return explode($delimiter, $string);
    }

    /**
     * Get field value from order data
     * Format: 12.Days (line item index 12, field Days)
     */
    protected function getFieldValue(string $fieldPath, array $orderData): string
    {
        // Handle line item field access (e.g., 12.Days, 12.Gram, 12.sku)
        if (preg_match('/^(\d+)\.(\w+)$/', $fieldPath, $matches)) {
            $itemIndex = intval($matches[1]);
            $fieldName = $matches[2];

            if (isset($orderData['line_items'][$itemIndex])) {
                $item = $orderData['line_items'][$itemIndex];
                
                // Check direct field
                if (isset($item[$fieldName])) {
                    return (string) $item[$fieldName];
                }

                // Check properties array
                if (isset($item['properties']) && is_array($item['properties'])) {
                    foreach ($item['properties'] as $property) {
                        if (isset($property['name']) && strtolower($property['name']) === strtolower($fieldName)) {
                            return (string) ($property['value'] ?? '');
                        }
                    }
                }
            }
        }

        // Handle nested field access (e.g., order.customer.email)
        $parts = explode('.', $fieldPath);
        $value = $orderData;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return '';
            }
        }

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Extract tags from order based on rule.
     * If php_rule is set, run it and return its result; otherwise use JSON/template logic.
     */
    public function extractTags(array $order, TaggingRule $rule): array
    {
        if (!empty($rule->php_rule)) {
            $rule->loadMissing('store');
            $store = $rule->relationLoaded('store') ? $rule->store : null;
            Log::info('TaggingEngineService::extractTags: Using PHP rule', [
                'rule_id' => $rule->id,
                'php_rule_length' => strlen($rule->php_rule),
                'has_store' => $store !== null,
            ]);
            $result = $this->executePhpRule($rule->php_rule, $order, $store);
            Log::info('TaggingEngineService::extractTags: PHP rule result', [
                'rule_id' => $rule->id,
                'tags_count' => count($result),
                'tags' => $result,
            ]);
            return $result;
        }

        $tags = [];

        // If rule has rules_json, evaluate conditions
        if ($rule->rules_json && is_array($rule->rules_json)) {
            $conditions = $rule->rules_json['conditions'] ?? [];
            $ruleTags = $rule->rules_json['tags'] ?? [];

            // Check if conditions match
            $conditionsMatch = true;
            foreach ($conditions as $condition) {
                if (!$this->evaluateCondition($condition, $order)) {
                    $conditionsMatch = false;
                    break;
                }
            }

            if ($conditionsMatch) {
                foreach ($ruleTags as $tag) {
                    // Check if tag contains expression
                    if (preg_match('/\{\{(.*?)\}\}/', $tag, $matches)) {
                        $expression = $matches[1];
                        $evaluated = $this->evaluateExpression('{{' . $expression . '}}', $order);
                        $tag = str_replace($matches[0], $evaluated, $tag);
                    }
                    $tags[] = $tag;
                }
            }
        }

        // If rule has tags_template, evaluate it
        if ($rule->tags_template) {
            $template = $rule->tags_template;
            
            // Replace all expressions in template
            while (preg_match('/\{\{(.*?)\}\}/', $template, $matches)) {
                $expression = $matches[1];
                $evaluated = $this->evaluateExpression('{{' . $expression . '}}', $order);
                $template = str_replace($matches[0], $evaluated, $template);
            }

            // Split by comma and add to tags
            $templateTags = array_filter(array_map('trim', explode(',', $template)));
            $tags = array_merge($tags, $templateTags);
        }

        return array_unique(array_filter($tags));
    }

    /**
     * Evaluate condition
     */
    protected function evaluateCondition(array $condition, array $orderData): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? '';

        $fieldValue = $this->getFieldValue($field, $orderData);

        return match ($operator) {
            'equals' => $fieldValue === $value,
            'contains' => str_contains($fieldValue, $value),
            'exists' => !empty($fieldValue),
            'greater_than' => floatval($fieldValue) > floatval($value),
            'less_than' => floatval($fieldValue) < floatval($value),
            default => false,
        };
    }

    /**
     * Execute PHP rule in isolated process.
     * Code receives \$order and must set \$tags (array of strings).
     * When \$store is provided, \$shopDomain and \$accessToken are also available for API calls (e.g. Shopify).
     * User code can use return; – it will only exit the inner closure, not the script.
     * Returns array of tag strings, or empty on error.
     */
    public function executePhpRule(string $phpCode, array $order, ?Store $store = null): array
    {
        // Remove <?php and ?> tags if present, as the wrapper already includes <?php
        $phpCode = preg_replace('/^<\?php\s*/i', '', trim($phpCode));
        $phpCode = preg_replace('/\?>\s*$/i', '', $phpCode);
        
        $orderJson = json_encode($order);
        if ($orderJson === false) {
            Log::warning('TaggingEngineService: failed to encode order for PHP rule');
            return [];
        }
        $orderEscaped = addcslashes($orderJson, "'\\");
        $shopDomain = '';
        $accessToken = '';
        if ($store) {
            $shopDomain = addcslashes((string) $store->shopify_store_url, "'\\");
            $accessToken = addcslashes((string) $store->shopify_access_token, "'\\");
        }
        $wrapper = <<<PHP
<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 0);
ob_start();
\$order = json_decode('{$orderEscaped}', true);
\$tags = [];
\$shopDomain = '{$shopDomain}';
\$accessToken = '{$accessToken}';
try {
    (function() use (&\$tags, \$order, \$shopDomain, \$accessToken) {
{$phpCode}
    })();
} catch (\Throwable \$e) {
    // Silently catch errors
}
ob_end_clean();
if (!is_array(\$tags)) {
    \$tags = [];
}
echo json_encode(array_values(array_filter(array_map('strval', \$tags))));
PHP;
        $tmpFile = tempnam(sys_get_temp_dir(), 'zyg_rule_');
        if ($tmpFile === false) {
            Log::warning('TaggingEngineService: could not create temp file for PHP rule');
            return [];
        }
        file_put_contents($tmpFile, $wrapper);
        $phpBinary = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        try {
            $descriptorSpec = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $proc = proc_open(
                $phpBinary . ' -d max_execution_time=10 -d memory_limit=64M ' . escapeshellarg($tmpFile),
                $descriptorSpec,
                $pipes,
                null,
                null,
                ['bypass_shell' => true]
            );
            if (!is_resource($proc)) {
                Log::warning('TaggingEngineService: proc_open failed for PHP rule');
                @unlink($tmpFile);
                return [];
            }
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($proc);
            @unlink($tmpFile);
            if ($stderr) {
                Log::warning('TaggingEngineService PHP rule stderr: ' . $stderr);
            }
            // Trim whitespace and newlines from output
            $stdout = trim($stdout);
            if (empty($stdout)) {
                Log::warning('TaggingEngineService: PHP rule returned empty output. stderr: ' . ($stderr ?: 'none'));
                return [];
            }
            // Try to extract JSON if there's extra output before/after
            if (preg_match('/\[.*\]/', $stdout, $matches)) {
                // Found array-like JSON, use it
                $stdout = $matches[0];
            } elseif (preg_match('/\{.*\}/', $stdout, $matches)) {
                // Found object-like JSON, use it
                $stdout = $matches[0];
            }
            $decoded = json_decode($stdout, true);
            if (!is_array($decoded)) {
                Log::warning('TaggingEngineService: PHP rule did not return valid JSON. stdout: ' . substr($stdout, 0, 500) . ', stderr: ' . ($stderr ?: 'none'));
                return [];
            }
            return array_values(array_filter(array_map('strval', $decoded)));
        } catch (\Throwable $e) {
            Log::warning('TaggingEngineService executePhpRule: ' . $e->getMessage());
            @unlink($tmpFile);
            return [];
        }
    }

    /**
     * Process order and update tags
     */
    public function processOrder(array $order, TaggingRule $rule, \App\Services\ShopifyService $shopifyService): bool
    {
        $tags = $this->extractTags($order, $rule);

        if (empty($tags)) {
            return true; // No tags to add, but not an error
        }

        return $shopifyService->updateOrderTags(
            $order['id'],
            $tags,
            $rule->overwrite_existing_tags
        );
    }
}
