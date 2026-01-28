<?php

use App\Http\Controllers\AutomationController;
use App\Http\Controllers\PlaygroundController;
use Illuminate\Support\Facades\Route;

Route::prefix('internal/playground')->group(function () {
    Route::post('/analyze', [PlaygroundController::class, 'analyze']);
    Route::post('/run', [PlaygroundController::class, 'run']);
});

Route::prefix('automations')->group(function () {
    Route::post('/{automation}/run', [AutomationController::class, 'runNow']);
    Route::post('/{automation}/webhook-url', [AutomationController::class, 'getWebhookUrl']);
});

Route::prefix('runs')->group(function () {
    Route::post('/{run}/retry', [AutomationController::class, 'retry']);
});
