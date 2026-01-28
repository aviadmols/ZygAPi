<?php

namespace App\Contracts;

use App\Domain\Automation\ActionResult;

interface ActionInterface
{
    public function name(): string;

    public function execute(array $context): ActionResult;

    public function simulate(array $context): ActionResult;

    public function requiredScopes(): array;
}
