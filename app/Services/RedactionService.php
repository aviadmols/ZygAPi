<?php

namespace App\Services;

class RedactionService
{
    private const SENSITIVE_HEADERS = [
        'authorization',
        'x-shopify-access-token',
        'token',
        'api_key',
        'secret',
        'password',
        'hmac',
    ];

    private const SENSITIVE_JSON_KEYS = [
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
        'password',
        'hmac',
    ];

    public function redactHeaders(array $headers): array
    {
        $redacted = [];
        foreach ($headers as $key => $value) {
            $lowerKey = strtolower($key);
            if (in_array($lowerKey, self::SENSITIVE_HEADERS, true)) {
                $redacted[$key] = '[REDACTED]';
            } else {
                $redacted[$key] = $value;
            }
        }
        return $redacted;
    }

    public function redactJson(array $data): array
    {
        return $this->redactJsonRecursive($data);
    }

    private function redactJsonRecursive($data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        $redacted = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            if (in_array($lowerKey, self::SENSITIVE_JSON_KEYS, true)) {
                $redacted[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $redacted[$key] = $this->redactJsonRecursive($value);
            } else {
                $redacted[$key] = $value;
            }
        }
        return $redacted;
    }

    public function redactRequest(array $request): array
    {
        if (isset($request['headers'])) {
            $request['headers'] = $this->redactHeaders($request['headers']);
        }
        if (isset($request['body'])) {
            $request['body'] = $this->redactJson($request['body']);
        }
        return $request;
    }

    public function redactResponse(array $response): array
    {
        if (isset($response['headers'])) {
            $response['headers'] = $this->redactHeaders($response['headers']);
        }
        if (isset($response['body'])) {
            $response['body'] = $this->redactJson($response['body']);
        }
        return $response;
    }
}
