<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Generate PHP Rule') }}
            </h2>
            <a href="{{ route('ai-conversations.index') }}" class="text-gray-600 hover:text-gray-900">Back to List</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">Store: <strong>{{ $aiConversation->store->name }}</strong></p>
                    </div>

                    <!-- Prompt + Sample order + Generate PHP -->
                    <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">1. Prompt and sample order</h3>
                        <label for="prompt-input" class="block text-sm font-medium text-gray-700 mb-1">Prompt (what to check and which tags to return)</label>
                        <textarea id="prompt-input" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 mb-3"
                            placeholder="e.g. Tag subscription vs one-time, add order_count from customer API, box from SKU (14D/28D + gram), Flow from line item property _Flow, high_ltv if total > 200"></textarea>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sample order (required for Generate PHP)</label>
                        <p class="text-xs text-gray-500 mb-2">Paste order JSON or fetch by order number so the AI can tailor the code.</p>
                        <textarea id="order-data" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm mb-2"
                            placeholder='Optional: {"id": "123", "line_items": [...]}'></textarea>
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="min-w-[200px]">
                                <label for="generate-order-id" class="block text-xs font-medium text-gray-600 mb-1">Or fetch by order number</label>
                                <input type="text" id="generate-order-id" placeholder="e.g. 1005"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>
                            <button type="button" id="generate-php-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm mt-5">
                                Generate PHP
                            </button>
                        </div>
                    </div>

                    <!-- PHP Rule -->
                    <div class="mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                        <h3 class="text-sm font-semibold text-gray-800 mb-2">2. PHP Rule</h3>
                        <p class="text-xs text-gray-600 mb-2">Generated code appears here. Edit if needed, then use Test below.</p>
                        <textarea id="php-rule-edit" rows="18" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                            placeholder="$tags = [];&#10;if (empty($order) || empty($order['line_items'])) { ... }"></textarea>
                    </div>

                    <!-- Test -->
                    <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <h3 class="text-sm font-semibold text-gray-800 mb-2">3. Test</h3>
                        <p class="text-xs text-gray-600 mb-3">Enter an order ID or number. The PHP Rule above will run and show which tags would be applied.</p>
                        <div class="flex flex-wrap items-end gap-4">
                            <div class="flex-1 min-w-[180px]">
                                <label for="test-order-id" class="block text-sm font-medium text-gray-700 mb-1">Order ID or order number</label>
                                <input type="text" id="test-order-id" placeholder="e.g. 1005 or Shopify order ID"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>
                            <button type="button" id="test-order-btn" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-4 rounded">
                                Test
                            </button>
                        </div>
                        <div id="test-order-results" class="mt-4 hidden"></div>
                        <div id="analyze-log-section" class="mt-4 hidden">
                            <button type="button" id="analyze-log-btn" class="bg-purple-500 hover:bg-purple-600 text-white font-medium py-2 px-4 rounded text-sm">
                                Analyze Log with AI
                            </button>
                            <div id="analyze-log-results" class="mt-4 hidden"></div>
                        </div>
                    </div>

                    <!-- Save Rule to Tagging Rules -->
                    <div class="mb-4">
                        <form id="generate-rule-form">
                            @csrf
                            <p class="text-sm text-gray-600 mb-2">Save the PHP rule above to Tagging Rules.</p>
                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Save Rule to Tagging Rules
                            </button>
                        </form>
                    </div>

                    @if($aiConversation->generatedRule)
                        <div class="mt-4 p-4 bg-green-100 border border-green-400 rounded-lg">
                            <p class="text-sm text-green-800">Rule saved!</p>
                            <a href="{{ route('tagging-rules.edit', $aiConversation->generatedRule) }}" class="text-blue-600 hover:text-blue-800">Edit Rule</a>
                        </div>
                    @endif

                    <!-- Debug log -->
                    <div class="mt-6 pt-6 border-t border-gray-300">
                        <details class="bg-gray-100 rounded-lg border border-gray-300" id="debug-log-details">
                            <summary class="px-4 py-3 cursor-pointer font-medium text-gray-700 select-none">Debug log</summary>
                            <div class="p-4">
                                <button type="button" id="debug-log-clear" class="text-xs text-gray-600 hover:text-gray-800 underline mb-2">Clear log</button>
                                <pre id="debug-log" class="text-xs font-mono bg-gray-900 text-green-400 p-4 rounded overflow-x-auto max-h-96 overflow-y-auto whitespace-pre-wrap break-words"></pre>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const generatePhpBtn = document.getElementById('generate-php-btn');
        const phpRuleEdit = document.getElementById('php-rule-edit');
        const promptInput = document.getElementById('prompt-input');
        const orderDataInput = document.getElementById('order-data');
        const generateRuleForm = document.getElementById('generate-rule-form');
        const debugLogEl = document.getElementById('debug-log');
        const debugLogDetails = document.getElementById('debug-log-details');

        function debugLog(label, obj) {
            if (!debugLogEl) return;
            const ts = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const line = '[' + ts + '] ' + label + '\n' + (typeof obj === 'string' ? obj : JSON.stringify(obj, null, 2)) + '\n\n';
            debugLogEl.textContent = (debugLogEl.textContent || '') + line;
            debugLogEl.scrollTop = debugLogEl.scrollHeight;
            if (debugLogDetails && !debugLogDetails.open) debugLogDetails.open = true;
        }
        document.getElementById('debug-log-clear')?.addEventListener('click', () => { if (debugLogEl) debugLogEl.textContent = ''; });

        if (generatePhpBtn && phpRuleEdit) {
            generatePhpBtn.addEventListener('click', async () => {
                const requirements = promptInput?.value?.trim() ?? '';
                const orderData = (orderDataInput?.value ?? '').trim();
                const orderId = document.getElementById('generate-order-id')?.value?.trim() ?? '';
                if (!requirements) {
                    alert('Enter a prompt describing what to check and which tags to return.');
                    return;
                }
                if (!orderData && !orderId) {
                    alert('Provide a sample order: paste Order JSON or enter an order number to fetch.');
                    return;
                }
                generatePhpBtn.disabled = true;
                generatePhpBtn.textContent = 'Generating...';
                try {
                    const response = await fetch('{{ route("ai-conversations.generate-php", $aiConversation) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            requirements: requirements,
                            order_data: orderData || null,
                            order_id: orderId || null
                        })
                    });
                    const data = await response.json();
                    debugLog('GENERATE_PHP – Response', { success: data.success, php_code_length: data.php_code ? data.php_code.length : 0, error: data.error || null });
                    if (data.success && data.php_code) {
                        phpRuleEdit.value = data.php_code;
                        alert('PHP code generated. Edit if needed, then use Test to see tags for an order.');
                    } else {
                        alert('Error: ' + (data.error || 'Unknown error'));
                    }
                } catch (err) {
                    debugLog('GENERATE_PHP – Error', err.message);
                    alert('Communication error: ' + err.message);
                } finally {
                    generatePhpBtn.disabled = false;
                    generatePhpBtn.textContent = 'Generate PHP';
                }
            });
        }

        if (generateRuleForm) {
            generateRuleForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const phpCode = phpRuleEdit?.value?.trim() ?? '';
                if (!phpCode) {
                    alert('Generate PHP first or paste PHP code in the PHP Rule field.');
                    return;
                }
                debugLog('GENERATE_RULE – Request (save php_code)', { php_code_length: phpCode.length });
                try {
                    const response = await fetch('{{ route("ai-conversations.generate-rule", $aiConversation) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ php_code: phpCode })
                    });
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        debugLog('GENERATE_RULE – Response (not JSON)', 'Status: ' + response.status);
                        alert('Server error. Status: ' + response.status);
                        return;
                    }
                    const data = await response.json();
                    debugLog('GENERATE_RULE – Response', { success: data.success, rule_id: data.rule?.id, error: data.error || null });
                    if (data.success) {
                        alert('Rule saved to Tagging Rules.');
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.error || data.message || 'Unknown error'));
                    }
                } catch (error) {
                    debugLog('GENERATE_RULE – Error', error.message);
                    alert('Communication error: ' + error.message);
                }
            });
        }

        const testOrderBtn = document.getElementById('test-order-btn');
        const testOrderResults = document.getElementById('test-order-results');
        if (testOrderBtn && testOrderResults) {
            testOrderBtn.addEventListener('click', async () => {
                const orderId = document.getElementById('test-order-id')?.value?.trim();
                if (!orderId) {
                    testOrderResults.innerHTML = '<p class="text-red-600 text-sm">Enter an order ID or order number.</p>';
                    testOrderResults.classList.remove('hidden');
                    return;
                }
                const phpCodeToSend = phpRuleEdit?.value?.trim() ?? '';
                if (!phpCodeToSend) {
                    testOrderResults.innerHTML = '<p class="text-amber-600 text-sm">Generate PHP first or paste PHP code in the PHP Rule field above.</p>';
                    testOrderResults.classList.remove('hidden');
                    return;
                }
                const body = {
                    order_id: orderId,
                    order_data: (orderDataInput?.value ?? '').trim() || null,
                    php_code: phpCodeToSend
                };
                debugLog('TEST_ORDER – Request', body);
                testOrderResults.innerHTML = '<p class="text-gray-500 text-sm">Running test...</p>';
                testOrderResults.classList.remove('hidden');
                try {
                    const response = await fetch('{{ route('ai-conversations.test-order', $aiConversation) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(body)
                    });
                    const data = await response.json();
                    const conversationType = '{{ $aiConversation->type ?? "tags" }}';
                    debugLog('TEST_ORDER – Response', { success: data.success, tags: data.tags || data.metafields || data.subscription_updates || null, error: data.error || null });
                    
                    // Log API information (condensed)
                    if (data.api_logs) {
                        debugLog('SHOPIFY ORDER SUMMARY', {
                            order_id: data.api_logs.shopify_order?.order_id,
                            order_number: data.api_logs.shopify_order?.order_number,
                            customer_email: data.api_logs.shopify_order?.customer_email,
                            line_items_count: data.api_logs.shopify_order?.line_items_count,
                            line_items_summary: data.api_logs.shopify_order?.line_items_summary,
                            tags: data.api_logs.shopify_order?.tags,
                            created_at: data.api_logs.shopify_order?.created_at
                        });
                        
                        if (data.api_logs.recharge_calls && data.api_logs.recharge_calls.length > 0) {
                            debugLog('RECHARGE API CALLS', data.api_logs.recharge_calls);
                        }
                    }
                    if (data.success) {
                        let html = '';
                        
                        if (conversationType === 'tags') {
                            const tags = data.tags || [];
                            html = '<p class="text-sm font-medium text-gray-700 mb-2">Tags that would be applied:</p>';
                            if (tags.length) {
                                html += '<div class="flex flex-wrap gap-2">' + tags.map(t => '<span class="bg-blue-500 text-white px-2 py-1 rounded text-sm">' + (t || '').replace(/</g, '&lt;') + '</span>').join('') + '</div>';
                            } else {
                                html += '<p class="text-gray-500 text-sm">No tags.</p>';
                            }
                        } else if (conversationType === 'metafields') {
                            const metafields = data.metafields || {};
                            const metafieldsList = data.metafields_list || [];
                            const summary = data.summary || {};
                            
                            html = '<div class="bg-green-50 border border-green-200 rounded-lg p-4">';
                            html += '<p class="text-sm font-semibold text-gray-800 mb-2">' + (summary.message || 'Metafields calculated') + '</p>';
                            
                            if (metafieldsList.length > 0) {
                                html += '<div class="mt-3 space-y-2">';
                                metafieldsList.forEach(mf => {
                                    html += '<div class="bg-white border border-gray-200 rounded p-2">';
                                    html += '<p class="text-xs font-mono text-gray-600">' + mf.full_key.replace(/</g, '&lt;') + '</p>';
                                    html += '<p class="text-sm text-gray-800 mt-1">' + String(mf.value || '').replace(/</g, '&lt;') + '</p>';
                                    html += '</div>';
                                });
                                html += '</div>';
                            } else {
                                html += '<p class="text-gray-500 text-sm mt-2">No metafields were set.</p>';
                            }
                            html += '</div>';
                        } else if (conversationType === 'recharge') {
                            const updates = data.subscription_updates || {};
                            const updatesList = data.updates_list || [];
                            const summary = data.summary || {};
                            
                            html = '<div class="bg-purple-50 border border-purple-200 rounded-lg p-4">';
                            html += '<p class="text-sm font-semibold text-gray-800 mb-2">' + (summary.message || 'Subscription updates calculated') + '</p>';
                            
                            if (updatesList.length > 0) {
                                html += '<div class="mt-3 space-y-2">';
                                updatesList.forEach(update => {
                                    html += '<div class="bg-white border border-gray-200 rounded p-2">';
                                    html += '<p class="text-xs font-semibold text-gray-600">' + update.field.replace(/</g, '&lt;') + '</p>';
                                    html += '<p class="text-sm text-gray-800 mt-1">' + String(update.value || '').replace(/</g, '&lt;') + '</p>';
                                    html += '</div>';
                                });
                                html += '</div>';
                            } else {
                                html += '<p class="text-gray-500 text-sm mt-2">No subscription updates were set.</p>';
                            }
                            html += '</div>';
                        }
                        
                        testOrderResults.innerHTML = html;
                        // Show analyze log button after successful test
                        document.getElementById('analyze-log-section')?.classList.remove('hidden');
                    } else {
                        testOrderResults.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm">' + (data.error || 'Error') + '</div>';
                        // Show analyze log button even on error
                        document.getElementById('analyze-log-section')?.classList.remove('hidden');
                    }
                } catch (err) {
                    debugLog('TEST_ORDER – Error', err.message);
                    testOrderResults.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm">Error: ' + err.message + '</div>';
                    // Show analyze log button even on error
                    document.getElementById('analyze-log-section')?.classList.remove('hidden');
                }
            });
        }

        // Analyze log with AI
        const analyzeLogBtn = document.getElementById('analyze-log-btn');
        const analyzeLogResults = document.getElementById('analyze-log-results');
        if (analyzeLogBtn) {
            analyzeLogBtn.addEventListener('click', async () => {
                const btn = analyzeLogBtn;
                const originalText = btn.textContent;
                btn.disabled = true;
                btn.textContent = 'Analyzing...';
                
                const logContent = debugLogEl?.textContent || '';
                const phpCode = phpRuleEdit?.value?.trim() || '';
                const prompt = promptInput?.value?.trim() || '';
                
                if (!logContent) {
                    alert('No log content available. Run a test first.');
                    btn.disabled = false;
                    btn.textContent = originalText;
                    return;
                }
                
                if (!phpCode) {
                    alert('No PHP code available.');
                    btn.disabled = false;
                    btn.textContent = originalText;
                    return;
                }
                
                analyzeLogResults.classList.remove('hidden');
                analyzeLogResults.innerHTML = '<p class="text-gray-500 text-sm">Analyzing log with AI...</p>';
                
                try {
                    const response = await fetch('{{ route("ai-conversations.analyze-test-log", $aiConversation) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            log_content: logContent,
                            php_code: phpCode,
                            prompt: prompt
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        let html = '<div class="bg-blue-50 border border-blue-200 rounded-lg p-4">';
                        html += '<h4 class="text-sm font-semibold text-gray-800 mb-3">AI Analysis & Recommendations</h4>';
                        
                        if (data.raw) {
                            html += '<div class="bg-white border border-gray-200 rounded p-3 text-sm text-gray-700 whitespace-pre-wrap">' + data.analysis.replace(/</g, '&lt;') + '</div>';
                        } else {
                            if (data.issues && data.issues.length > 0) {
                                html += '<div class="mb-3">';
                                html += '<p class="text-sm font-semibold text-red-700 mb-2">Issues Found:</p>';
                                html += '<ul class="list-disc list-inside space-y-1">';
                                data.issues.forEach(issue => {
                                    html += '<li class="text-sm text-gray-700">' + String(issue).replace(/</g, '&lt;') + '</li>';
                                });
                                html += '</ul></div>';
                            }
                            
                            if (data.recommendations && data.recommendations.length > 0) {
                                html += '<div class="mb-3">';
                                html += '<p class="text-sm font-semibold text-blue-700 mb-2">Recommendations:</p>';
                                html += '<ul class="list-disc list-inside space-y-1">';
                                data.recommendations.forEach(rec => {
                                    html += '<li class="text-sm text-gray-700">' + String(rec).replace(/</g, '&lt;') + '</li>';
                                });
                                html += '</ul></div>';
                            }
                            
                            if (data.suggested_fixes) {
                                html += '<div class="mb-3">';
                                html += '<p class="text-sm font-semibold text-green-700 mb-2">Suggested Fixes:</p>';
                                html += '<pre class="bg-gray-900 text-green-400 p-3 rounded text-xs overflow-x-auto">' + String(data.suggested_fixes).replace(/</g, '&lt;') + '</pre>';
                                html += '</div>';
                            }
                            
                            if (data.suggested_prompt) {
                                html += '<div class="mb-3">';
                                html += '<div class="flex items-center justify-between mb-2">';
                                html += '<p class="text-sm font-semibold text-purple-700">Improved Prompt:</p>';
                                html += '<button type="button" onclick="copySuggestedPrompt()" class="text-xs bg-purple-500 hover:bg-purple-600 text-white px-3 py-1 rounded">Copy & Use</button>';
                                html += '</div>';
                                html += '<textarea id="suggested-prompt-text" readonly class="w-full bg-gray-50 border border-gray-300 rounded p-3 text-sm font-mono" rows="4">' + String(data.suggested_prompt).replace(/</g, '&lt;') + '</textarea>';
                                html += '<p class="text-xs text-gray-500 mt-1">Copy this improved prompt and use it in the Generate PHP section above.</p>';
                                html += '</div>';
                            }
                        }
                        
                        html += '</div>';
                        analyzeLogResults.innerHTML = html;
                    } else {
                        analyzeLogResults.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm">Error: ' + (data.error || 'Failed to analyze log') + '</div>';
                    }
                } catch (error) {
                    analyzeLogResults.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm">Error: ' + error.message + '</div>';
                } finally {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            });
        }

        // Copy suggested prompt to prompt input
        window.copySuggestedPrompt = function() {
            const suggestedPrompt = document.getElementById('suggested-prompt-text');
            const promptInput = document.getElementById('prompt-input');
            if (suggestedPrompt && promptInput) {
                promptInput.value = suggestedPrompt.value;
                promptInput.focus();
                alert('Prompt copied! You can now use "Generate PHP" with the improved prompt.');
            }
        };
    </script>
</x-app-layout>
