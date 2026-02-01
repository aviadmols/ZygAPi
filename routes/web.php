<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Stores
    Route::resource('stores', \App\Http\Controllers\StoreController::class);

    // Tagging Rules
    Route::resource('tagging-rules', \App\Http\Controllers\TaggingRuleController::class);
    Route::post('tagging-rules/{tagging_rule}/test', [\App\Http\Controllers\TaggingRuleController::class, 'test'])->name('tagging-rules.test');
    Route::get('tagging-rules', [\App\Http\Controllers\TaggingRuleController::class, 'index'])->name('tagging-rules.index');

    // Order Processing
    Route::get('orders/process', [\App\Http\Controllers\OrderProcessingController::class, 'index'])->name('orders.process.index');
    Route::post('orders/process', [\App\Http\Controllers\OrderProcessingController::class, 'processOrders'])->name('orders.process');
    Route::get('orders/progress/{orderProcessingJob}', [\App\Http\Controllers\OrderProcessingController::class, 'getProgress'])->name('orders.progress');
    Route::get('orders/results/{orderProcessingJob}', [\App\Http\Controllers\OrderProcessingController::class, 'getResults'])->name('orders.results');

    // AI Conversations
    Route::resource('ai-conversations', \App\Http\Controllers\AiConversationController::class);
    Route::post('ai-conversations/{ai_conversation}/chat', [\App\Http\Controllers\AiConversationController::class, 'chat'])->name('ai-conversations.chat');
    Route::post('ai-conversations/{ai_conversation}/generate-rule', [\App\Http\Controllers\AiConversationController::class, 'generateRule'])->name('ai-conversations.generate-rule');
});

// Webhooks (without auth - with HMAC verification)
Route::post('webhooks/shopify/order-created', [\App\Http\Controllers\WebhookController::class, 'handleOrderCreated'])->name('webhooks.shopify.order-created');

require __DIR__.'/auth.php';
