<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $activeRules = \App\Models\TaggingRule::with('store')
        ->where('is_active', true)
        ->latest()
        ->get();
    
    $recentLogs = \App\Models\TaggingRuleLog::with('taggingRule')
        ->latest()
        ->limit(10)
        ->get();
    
    return view('dashboard', compact('activeRules', 'recentLogs'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Stores
    Route::resource('stores', \App\Http\Controllers\StoreController::class);

    // Tagging Rules
    Route::resource('tagging-rules', \App\Http\Controllers\TaggingRuleController::class);
    Route::post('tagging-rules/preview', [\App\Http\Controllers\TaggingRuleController::class, 'preview'])->name('tagging-rules.preview');
    Route::post('tagging-rules/generate-php', [\App\Http\Controllers\TaggingRuleController::class, 'generatePhp'])->name('tagging-rules.generate-php');
    Route::post('tagging-rules/{tagging_rule}/generate-name-description', [\App\Http\Controllers\TaggingRuleController::class, 'generateNameAndDescription'])->name('tagging-rules.generate-name-description');
    Route::post('tagging-rules/{tagging_rule}/test', [\App\Http\Controllers\TaggingRuleController::class, 'test'])->name('tagging-rules.test');
    Route::get('tagging-rules/{tagging_rule}/tags', [\App\Http\Controllers\TaggingRuleController::class, 'tags'])->name('tagging-rules.tags');
    Route::post('tagging-rules/{tagging_rule}/tags', [\App\Http\Controllers\TaggingRuleController::class, 'tags'])->name('tagging-rules.tags.post');
    Route::get('tagging-rules', [\App\Http\Controllers\TaggingRuleController::class, 'index'])->name('tagging-rules.index');
    Route::get('tagging-rule-logs', [\App\Http\Controllers\TaggingRuleLogController::class, 'index'])->name('tagging-rule-logs.index');

    // Order Processing
    Route::get('orders/process', [\App\Http\Controllers\OrderProcessingController::class, 'index'])->name('orders.process.index');
    Route::post('orders/process', [\App\Http\Controllers\OrderProcessingController::class, 'processOrders'])->name('orders.process');
    Route::get('orders/progress/{orderProcessingJob}', [\App\Http\Controllers\OrderProcessingController::class, 'getProgress'])->name('orders.progress');
    Route::get('orders/results/{orderProcessingJob}', [\App\Http\Controllers\OrderProcessingController::class, 'getResults'])->name('orders.results');

    // AI Conversations
    Route::resource('ai-conversations', \App\Http\Controllers\AiConversationController::class);
    Route::post('ai-conversations/{ai_conversation}/chat', [\App\Http\Controllers\AiConversationController::class, 'chat'])->name('ai-conversations.chat');
    Route::post('ai-conversations/{ai_conversation}/test-order', [\App\Http\Controllers\AiConversationController::class, 'testOrder'])->name('ai-conversations.test-order');
    Route::post('ai-conversations/{ai_conversation}/generate-php', [\App\Http\Controllers\AiConversationController::class, 'generatePhp'])->name('ai-conversations.generate-php');
    Route::post('ai-conversations/{ai_conversation}/generate-rule', [\App\Http\Controllers\AiConversationController::class, 'generateRule'])->name('ai-conversations.generate-rule');

    // AI Prompt Management
    Route::get('prompt-templates', [\App\Http\Controllers\PromptTemplateController::class, 'index'])->name('prompt-templates.index');
    Route::get('prompt-templates/{promptTemplate}/edit', [\App\Http\Controllers\PromptTemplateController::class, 'edit'])->name('prompt-templates.edit');
    Route::put('prompt-templates/{promptTemplate}', [\App\Http\Controllers\PromptTemplateController::class, 'update'])->name('prompt-templates.update');

    // OpenRouter Settings (API key + model)
    Route::get('settings/openrouter', [\App\Http\Controllers\OpenRouterSettingsController::class, 'index'])->name('settings.openrouter.index');
    Route::put('settings/openrouter', [\App\Http\Controllers\OpenRouterSettingsController::class, 'update'])->name('settings.openrouter.update');
});

// Webhooks (without auth - with HMAC verification)
Route::post('webhooks/shopify/order-created', [\App\Http\Controllers\WebhookController::class, 'handleOrderCreated'])->name('webhooks.shopify.order-created');

// Tagging rule apply: run rule on order and update Shopify tags. Auth: session OR X-Webhook-Token / ?token
Route::post('webhooks/tagging-rule/{tagging_rule}/apply', [\App\Http\Controllers\TaggingRuleController::class, 'apply'])->name('webhooks.tagging-rule.apply');

require __DIR__.'/auth.php';
