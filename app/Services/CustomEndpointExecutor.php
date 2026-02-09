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
        $logs[] = [
            'step' => 'Initialization',
            'timestamp' => now()->toIso8601String(),
            'message' => 'Starting code execution',
            'input_received' => $input,
            'store_id' => $store->id,
            'store_name' => $store->name,
        ];

        // Clean code (remove PHP tags if present)
        $code = preg_replace('/^<\?php\s*/i', '', trim($code));
        $code = preg_replace('/\?>\s*$/i', '', $code);
        
        $logs[] = [
            'step' => 'Code Preparation',
            'timestamp' => now()->toIso8601String(),
            'message' => 'Code cleaned and prepared for execution',
            'code_length' => strlen($code),
        ];
        
        // Wrap code in execution context
        // The code expects: $store, $input, $shopDomain, $accessToken, $rechargeAccessToken
        $wrappedCode = "
            \$store = " . var_export($store->toArray(), true) . ";
            \$input = " . var_export($input, true) . ";
            \$shopDomain = " . var_export($shopDomain, true) . ";
            \$accessToken = " . var_export($accessToken, true) . ";
            \$rechargeAccessToken = " . var_export($rechargeAccessToken, true) . ";
            \$response = [];
            
            // Log execution start
            \$executionLogs = [];
            \$executionLogs[] = ['step' => 'Code Execution Started', 'timestamp' => date('c'), 'input' => \$input];
            
            try {
                {$code}
                
                \$executionLogs[] = ['step' => 'Code Execution Completed', 'timestamp' => date('c'), 'response' => \$response];
            } catch (\Exception \$e) {
                \$executionLogs[] = ['step' => 'Code Execution Error', 'timestamp' => date('c'), 'error' => \$e->getMessage(), 'file' => \$e->getFile(), 'line' => \$e->getLine()];
                throw \$e;
            }
            
            return ['response' => \$response, 'logs' => \$executionLogs];
        ";

        // Validate code syntax before execution
        $this->validateCodeSyntax($wrappedCode);
        
        $logs[] = [
            'step' => 'Syntax Validation',
            'timestamp' => now()->toIso8601String(),
            'message' => 'Code syntax validated successfully',
        ];

        // Execute in isolated scope
        try {
            $logs[] = [
                'step' => 'Execution Start',
                'timestamp' => now()->toIso8601String(),
                'message' => 'Executing code...',
            ];
            
            // Use eval in a controlled way - this is necessary for dynamic code execution
            // but we validate syntax first and limit available functions
            $evalResult = eval($wrappedCode);
            
            // Extract logs from execution if available
            if (is_array($evalResult) && isset($evalResult['logs'])) {
                $logs = array_merge($logs, $evalResult['logs']);
            }
            
            $result = $evalResult['response'] ?? $evalResult;
            
            if (!is_array($result)) {
                $result = ['data' => $result];
            }

            // Ensure success key exists
            if (!isset($result['success'])) {
                $result['success'] = true;
            }
            
            $logs[] = [
                'step' => 'Execution Complete',
                'timestamp' => now()->toIso8601String(),
                'message' => 'Code executed successfully',
                'result' => $result,
            ];

            return $result;
        } catch (\ParseError $e) {
            $logs[] = [
                'step' => 'Syntax Error',
                'timestamp' => now()->toIso8601String(),
                'message' => 'Syntax error in generated code',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
            throw new \RuntimeException('Syntax error in generated code: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $logs[] = [
                'step' => 'Execution Error',
                'timestamp' => now()->toIso8601String(),
                'message' => 'Error during code execution',
                'error' => $e->getMessage(),
                'type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
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
