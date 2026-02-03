<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Generate PHP Rule') }} - {{ ucfirst($aiConversation->type ?? 'tags') }}
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
                        <label for="prompt-input" class="block text-sm font-medium text-gray-700 mb-1">
                            @if(($aiConversation->type ?? 'tags') === 'tags')
                                Prompt (what to check and which tags to return)
                            @elseif(($aiConversation->type ?? 'tags') === 'metafields')
                                Prompt (what metafields to set and their values, e.g. fulfillment_date based on order date + 12 days)
                            @elseif(($aiConversation->type ?? 'tags') === 'recharge')
                                Prompt (what subscription settings to update, e.g. next_charge_scheduled_at, quantity, etc.)
                            @endif
                        </label>
                        <textarea id="prompt-input" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 mb-3"
                            placeholder="@if(($aiConversation->type ?? 'tags') === 'tags')e.g. Tag subscription vs one-time, add order_count from customer API, box from SKU (14D/28D + gram), Flow from line item property _Flow, high_ltv if total > 200@elseif(($aiConversation->type ?? 'tags') === 'metafields')e.g. Set fulfillment_date to order date + 12 days in format YYYY-MM-DDT12:00:00Z@elseif(($aiConversation->type ?? 'tags') === 'recharge')e.g. Update next_charge_scheduled_at to 30 days from now, update quantity to 2@endif"></textarea>
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
                            placeholder="@if(($aiConversation->type ?? 'tags') === 'tags')$tags = [];&#10;if (empty($order) || empty($order['line_items'])) { ... }@elseif(($aiConversation->type ?? 'tags') === 'metafields')$metafields = [];&#10;$metafields['custom']['fulfillment_date'] = date('Y-m-d\TH:i:s\Z', strtotime($order['created_at'] . ' +12 days'));@elseif(($aiConversation->type ?? 'tags') === 'recharge')$subscriptionUpdates = [];&#10;$subscriptionUpdates['next_charge_scheduled_at'] = date('Y-m-d', strtotime('+30 days'));@endif"></textarea>
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
                    </div>

                    <!-- Save Rule to Tagging Rules (only for tags type) -->
                    @if(($aiConversation->type ?? 'tags') === 'tags')
                    <div class="mb-4">
                        <form id="generate-rule-form">
                            @csrf
                            <p class="text-sm text-gray-600 mb-2">Save the PHP rule above to Tagging Rules.</p>
                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Save Rule to Tagging Rules
                            </button>
                        </form>
                    </div>
                    @else
                    <div class="mb-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <p class="text-sm text-gray-700 mb-2">
                            @if(($aiConversation->type ?? 'tags') === 'metafields')
                                This rule will update order metafields. Use the Test button above to verify it works with a specific order number.
                            @elseif(($aiConversation->type ?? 'tags') === 'recharge')
                                This rule will update Recharge subscription settings. Use the Test button above to verify it works with a specific order number.
                            @endif
                        </p>
                    </div>
                    @endif

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
                    const conversationType = '{{ $aiConversation->type ?? 'tags' }}';
                    debugLog('TEST_ORDER – Response', { success: data.success, data: data, error: data.error || null });
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
                            html = '<p class="text-sm font-medium text-gray-700 mb-2">Metafields that would be updated:</p>';
                            if (Object.keys(metafields).length > 0) {
                                html += '<div class="bg-gray-50 rounded p-3 font-mono text-xs">';
                                for (const [namespace, fields] of Object.entries(metafields)) {
                                    if (typeof fields === 'object') {
                                        for (const [key, value] of Object.entries(fields)) {
                                            html += '<div class="mb-1"><span class="font-semibold">' + namespace + '.' + key + ':</span> <span class="text-gray-700">' + String(value).replace(/</g, '&lt;') + '</span></div>';
                                        }
                                    }
                                }
                                html += '</div>';
                            } else {
                                html += '<p class="text-gray-500 text-sm">No metafields.</p>';
                            }
                        } else if (conversationType === 'recharge') {
                            const updates = data.subscription_updates || {};
                            html = '<p class="text-sm font-medium text-gray-700 mb-2">Subscription updates that would be applied:</p>';
                            if (Object.keys(updates).length > 0) {
                                html += '<div class="bg-gray-50 rounded p-3 font-mono text-xs">';
                                for (const [key, value] of Object.entries(updates)) {
                                    html += '<div class="mb-1"><span class="font-semibold">' + key + ':</span> <span class="text-gray-700">' + String(value).replace(/</g, '&lt;') + '</span></div>';
                                }
                                html += '</div>';
                            } else {
                                html += '<p class="text-gray-500 text-sm">No subscription updates.</p>';
                            }
                        }
                        testOrderResults.innerHTML = html;
                    } else {
                        testOrderResults.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm">' + (data.error || 'Error') + '</div>';
                    }
                } catch (err) {
                    debugLog('TEST_ORDER – Error', err.message);
                    testOrderResults.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm">Error: ' + err.message + '</div>';
                }
            });
        }
    </script>
</x-app-layout>
