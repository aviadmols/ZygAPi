<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('webhooks')->group(function () {
    Route::post('/shopify/{shop}/orders/create', [WebhookController::class, 'handleShopify'])
        ->defaults('event', 'orders/create');
    Route::post('/shopify/{shop}/orders/update', [WebhookController::class, 'handleShopify'])
        ->defaults('event', 'orders/update');
    Route::post('/recharge/{shop}/subscription/created', [WebhookController::class, 'handleRecharge'])
        ->defaults('event', 'subscription/created');
    Route::post('/recharge/{shop}/subscription/updated', [WebhookController::class, 'handleRecharge'])
        ->defaults('event', 'subscription/updated');
});
