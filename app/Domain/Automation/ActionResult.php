<?php

namespace App\Domain\Automation;

class ActionResult
{
    public function __construct(
        public string $status,
        public ?array $data = null,
        public ?string $error = null,
        public ?array $simulationDiff = null,
        public ?array $httpRequest = null,
        public ?array $httpResponse = null,
    ) {
    }

    public static function success(array $data = null, array $httpRequest = null, array $httpResponse = null): self
    {
        return new self('success', $data, null, null, $httpRequest, $httpResponse);
    }

    public static function error(string $error, array $httpRequest = null, array $httpResponse = null): self
    {
        return new self('error', null, $error, null, $httpRequest, $httpResponse);
    }

    public static function simulated(array $simulationDiff, array $httpRequest = null): self
    {
        return new self('simulated', null, null, $simulationDiff, $httpRequest, null);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isError(): bool
    {
        return $this->status === 'error';
    }

    public function isSimulated(): bool
    {
        return $this->status === 'simulated';
    }
}
