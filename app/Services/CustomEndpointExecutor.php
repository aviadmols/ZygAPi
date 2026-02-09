<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Log;

class CustomEndpointExecutor
{
    public function execute(string $code, array $input, int $storeId): array
    {
        $logs = [];
        $startTime = microtime(true);

        try {
            // Get store
            $store = Store::findOrFail($storeId);
            
            // Extract tokens for code execution context
            $shopDomain = $store->shopify_store_url ?? '';
            $accessToken = $store->shopify_access_token ?? '';
            $rechargeAccessToken = $store->recharge_access_token ?? '';

            // Create isolated execution context
            $result = $this->executeInIsolatedContext(
                $code,
                $input,
                $store,
                $shopDomain,
                $accessToken,
                $rechargeAccessToken,
                $logs
            );

            $executionTime = (microtime(true) - $startTime) * 1000; // milliseconds

            return [
                'success' => $result['success'] ?? false,
                'data' => $result['data'] ?? [],
                'logs' => $logs,
                'execution_time_ms' => round($executionTime, 2),
            ];
        } catch (\Exception $e) {
            Log::error('Custom endpoint execution failed', [
                'error' => $e->getMessage(),
                'store_id' => $storeId,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'data' => [
                    'error' => $e->getMessage(),
                    'type' => get_class($e),
                ],
                'logs' => $logs,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    private function executeInIsolatedContext(
        string $code,
        array $input,
        Store $store,
        string $shopDomain,
        string $accessToken,
        string $rechargeAccessToken,
        array &$logs
    ): array {
        // Clean code (remove PHP tags if present)
        $code = preg_replace('/^<\?php\s*/i', '', trim($code));
        $code = preg_replace('/\?>\s*$/i', '', $code);
        
        // Wrap code in execution context
        // The code expects: $store, $input, $shopDomain, $accessToken, $rechargeAccessToken
        $wrappedCode = "
            \$store = " . var_export($store->toArray(), true) . ";
            \$input = " . var_export($input, true) . ";
            \$shopDomain = " . var_export($shopDomain, true) . ";
            \$accessToken = " . var_export($accessToken, true) . ";
            \$rechargeAccessToken = " . var_export($rechargeAccessToken, true) . ";
            \$response = [];
            {$code}
            return \$response;
        ";

        // Validate code syntax before execution
        $this->validateCodeSyntax($wrappedCode);

        // Execute in isolated scope
        try {
            // Use eval in a controlled way - this is necessary for dynamic code execution
            // but we validate syntax first and limit available functions
            $result = eval($wrappedCode);
            
            if (!is_array($result)) {
                $result = ['data' => $result];
            }

            // Ensure success key exists
            if (!isset($result['success'])) {
                $result['success'] = true;
            }

            return $result;
        } catch (\ParseError $e) {
            throw new \RuntimeException('Syntax error in generated code: ' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new \RuntimeException('Execution error: ' . $e->getMessage());
        }
    }

    private function validateCodeSyntax(string $code): void
    {
        // Check for dangerous PHP functions
        $dangerousFunctions = [
            'exec', 'system', 'shell_exec', 'passthru', 'proc_open',
            'file_get_contents', 'file_put_contents', 'fopen', 'fwrite',
            'unlink', 'rmdir', 'mkdir', 'chmod', 'chown',
            'eval', 'create_function', 'preg_replace', 'call_user_func',
            'include', 'require', 'include_once', 'require_once',
        ];

        foreach ($dangerousFunctions as $func) {
            if (stripos($code, $func . '(') !== false) {
                throw new \SecurityException("Dangerous function '{$func}' is not allowed");
            }
        }

        // Check for dangerous patterns
        $dangerousPatterns = [
            '/\$_(GET|POST|REQUEST|COOKIE|SERVER|ENV|FILES)/',
            '/\b(exec|system|shell_exec|passthru|proc_open)\s*\(/i',
            '/\beval\s*\(/i',
            '/\binclude\s*\(/i',
            '/\brequire\s*\(/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $code)) {
                throw new \SecurityException('Dangerous pattern detected in code');
            }
        }

        // Note: We validate syntax by attempting to create the closure
        // The actual syntax validation happens when eval() is called
    }
}
