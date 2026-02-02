<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('AI Conversation') }}
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

                    <!-- Chat Messages -->
                    <div id="chat-messages" class="mb-4 h-96 overflow-y-auto border border-gray-200 rounded-lg p-4 bg-gray-50">
                        @foreach($aiConversation->messages ?? [] as $message)
                            <div class="mb-4 {{ $message['role'] === 'user' ? 'text-right' : 'text-left' }}" data-role="{{ $message['role'] ?? 'assistant' }}">
                                <div class="inline-block max-w-3xl p-3 rounded-lg {{ $message['role'] === 'user' ? 'bg-blue-500 text-white' : 'bg-white border border-gray-300' }}">
                                    <p class="text-sm">{{ $message['content'] }}</p>
                                    @if(isset($message['timestamp']))
                                        <p class="text-xs mt-1 opacity-75">{{ $message['timestamp'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Chat Input -->
                    <form id="chat-form" class="mb-4">
                        @csrf
                        <div class="flex">
                            <textarea id="message-input" name="message" rows="3" required
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter your rules here..."></textarea>
                            <button type="submit" class="ml-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Send
                            </button>
                        </div>
                    </form>

                    <!-- Sample order + Generate PHP + PHP Rule + Test -->
                    @if(count($aiConversation->messages ?? []) > 0)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sample order for PHP generation</label>
                            <p class="text-xs text-gray-500 mb-2">Paste order JSON or enter an order number. Required for &quot;Generate PHP&quot; so the AI can tailor the code.</p>
                            <textarea id="order-data" rows="4"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                                placeholder='{"id": "123", "line_items": [...]}'></textarea>
                            <div class="mt-2 flex flex-wrap items-center gap-4">
                                <div class="min-w-[200px]">
                                    <label for="generate-order-id" class="block text-xs font-medium text-gray-600 mb-1">Or fetch by order number</label>
                                    <input type="text" id="generate-order-id" placeholder="e.g. 1005"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                </div>
                                <button type="button" id="generate-php-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded text-sm">
                                    Generate PHP
                                </button>
                            </div>
                        </div>
                        <div class="mb-4 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">PHP Rule</h3>
                            <p class="text-xs text-gray-600 mb-2">Click &quot;Generate PHP&quot; above to fill this from your conversation (structure: $tags = [], early return, subscription, box, Flow, high_ltv). Edit if needed, then test with an order number below.</p>
                            <label for="php-rule-edit" class="block text-sm font-medium text-gray-700 mb-1">PHP code (sets $tags from $order)</label>
                            <textarea id="php-rule-edit" rows="16" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                                placeholder="$tags = [];&#10;if (empty($order) || empty($order['line_items'])) { ... }"></textarea>
                        </div>
                        <div class="mb-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">Test with order number</h3>
                            <p class="text-xs text-gray-600 mb-3">Enter an order ID or number. The PHP Rule above will run on that order and show which tags would be applied.</p>
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
                    @endif

                    <!-- Generate Rule Button (PHP rule → Tagging Rules) -->
                    @if(count($aiConversation->messages ?? []) > 2)
                        <form id="generate-rule-form" class="mb-4">
                            @csrf
                            <input type="hidden" id="user-requirements" name="user_requirements">
                            <p class="text-sm text-gray-600 mb-2">Save the PHP rule above to Tagging Rules. Uses the sample order (JSON or order number) from above.</p>
                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Save Rule to Tagging Rules
                            </button>
                        </form>
                    @endif

                    @if($aiConversation->generatedRule)
                        <div class="mt-4 p-4 bg-green-100 border border-green-400 rounded-lg">
                            <p class="text-sm text-green-800">Rule generated successfully!</p>
                            <a href="{{ route('tagging-rules.edit', $aiConversation->generatedRule) }}" class="text-blue-600 hover:text-blue-800">Edit Rule</a>
                        </div>
                    @endif

                    <!-- Debug log: request/response at each step -->
                    <div class="mt-6 pt-6 border-t border-gray-300">
                        <details class="bg-gray-100 rounded-lg border border-gray-300" id="debug-log-details">
                            <summary class="px-4 py-3 cursor-pointer font-medium text-gray-700 select-none">Debug log – request and response for each step</summary>
                            <div class="p-4">
                                <p class="text-xs text-gray-500 mb-2">Each action (Send, Test, Generate Rule) is shown here with request and response.</p>
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
        const chatForm = document.getElementById('chat-form');
        const chatMessages = document.getElementById('chat-messages');
        const messageInput = document.getElementById('message-input');
        const generateRuleForm = document.getElementById('generate-rule-form');
        const orderDataInput = document.getElementById('order-data');
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

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            const body = { message: message, order_data: orderDataInput?.value ?? null };
            debugLog('CHAT – Request (sent)', body);

            addMessage('user', message);
            messageInput.value = '';

            try {
                const response = await fetch(`{{ route('ai-conversations.chat', $aiConversation) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(body)
                });

                const data = await response.json();
                debugLog('CHAT – Response (received)', { success: data.success, message_length: data.message ? data.message.length : 0, error: data.error || null });
                if (data.success) {
                    addMessage('assistant', data.message);
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                debugLog('CHAT – Error', error.message);
                alert('Communication error: ' + error.message);
            }
        });

        const generatePhpBtn = document.getElementById('generate-php-btn');
        const phpRuleEdit = document.getElementById('php-rule-edit');
        if (generatePhpBtn && phpRuleEdit) {
            generatePhpBtn.addEventListener('click', async () => {
                const orderData = orderDataInput ? orderDataInput.value.trim() : '';
                const orderId = document.getElementById('generate-order-id')?.value?.trim() || '';
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
                        body: JSON.stringify({ order_data: orderData || null, order_id: orderId || null })
                    });
                    const data = await response.json();
                    debugLog('GENERATE_PHP – Response', { success: data.success, php_code_length: data.php_code ? data.php_code.length : 0, error: data.error || null });
                    if (data.success && data.php_code) {
                        phpRuleEdit.value = data.php_code;
                        alert('PHP code generated. Edit if needed, then enter an order number and click Test to see tags.');
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
                const orderData = (orderDataInput?.value ?? '').trim();
                const orderIdForSample = document.getElementById('generate-order-id')?.value?.trim() || null;
                if (!orderData && !orderIdForSample) {
                    alert('Please enter Sample Order (JSON) or an Order number to fetch as sample.');
                    return;
                }

                const userMessages = Array.from(chatMessages.querySelectorAll('[data-role="user"]'))
                    .map(el => el.textContent.trim())
                    .join('\n');
                const userReqEl = document.getElementById('user-requirements');
                if (userReqEl) userReqEl.value = userMessages;

                const body = { order_data: orderData || null, order_id: orderIdForSample, user_requirements: userMessages };
                debugLog('GENERATE_RULE – Request (sent)', body);

                try {
                    const response = await fetch(`{{ route('ai-conversations.generate-rule', $aiConversation) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(body)
                    });

                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        debugLog('GENERATE_RULE – Response (not JSON)', 'Status: ' + response.status + '\n' + text.slice(0, 500));
                        alert('Server error (received HTML instead of JSON). Set APP_DEBUG=true on Railway to see details. Status: ' + response.status);
                        return;
                    }

                    const data = await response.json();
                    debugLog('GENERATE_RULE – Response (received)', { success: data.success, rule_id: data.rule?.id, rule_name: data.rule?.name, error: data.error || null });
                    if (data.success) {
                        alert('Rule generated successfully! You can edit and test it in Tagging Rules.');
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
                const phpRuleEdit = document.getElementById('php-rule-edit');
                const phpCodeToSend = phpRuleEdit ? phpRuleEdit.value.trim() : '';
                if (!phpCodeToSend) {
                    const userMessages = Array.from(chatMessages.querySelectorAll('[data-role="user"]'))
                        .map(el => el.textContent.trim())
                        .join('\n');
                    if (!userMessages) {
                        testOrderResults.innerHTML = '<p class="text-amber-600 text-sm">Send at least one message describing what to check and which tags to return, or paste PHP code in the PHP Rule field above.</p>';
                        testOrderResults.classList.remove('hidden');
                        return;
                    }
                }
                const body = { order_id: orderId, order_data: (orderDataInput?.value ?? '').trim() || null, php_code: phpCodeToSend || null };
                debugLog('TEST_ORDER – Request (sent)', body);

                testOrderResults.innerHTML = '<p class="text-gray-500 text-sm">Running test...</p>';
                testOrderResults.classList.remove('hidden');
                try {
                    const response = await fetch(`{{ route('ai-conversations.test-order', $aiConversation) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(body)
                    });
                    const data = await response.json();
                    debugLog('TEST_ORDER – Response (received)', { success: data.success, tags: data.tags || [], tags_count: (data.tags || []).length, php_code_length: data.php_code ? data.php_code.length : 0, error: data.error || null });
                    if (data.success) {
                        if (data.php_code && phpRuleEdit) phpRuleEdit.value = data.php_code;
                        const tags = data.tags || [];
                        let html = '<p class="text-sm font-medium text-gray-700 mb-2">Tags that would be applied:</p>';
                        if (tags.length) {
                            html += '<div class="flex flex-wrap gap-2 mb-3">' + tags.map(t => '<span class="bg-blue-500 text-white px-2 py-1 rounded text-sm">' + (t || '').replace(/</g, '&lt;') + '</span>').join('') + '</div>';
                        } else {
                            html += '<p class="text-gray-500 text-sm mb-3">No tags.</p>';
                        }
                        if (data.php_code) {
                            html += '<p class="text-xs text-gray-500 mt-2">PHP code updated in the PHP Rule field above. Edit and run Test again if needed.</p>';
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

        function addMessage(role, content) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `mb-4 ${role === 'user' ? 'text-right' : 'text-left'}`;
            messageDiv.setAttribute('data-role', role);
            messageDiv.innerHTML = `
                <div class="inline-block max-w-3xl p-3 rounded-lg ${role === 'user' ? 'bg-blue-500 text-white' : 'bg-white border border-gray-300'}">
                    <p class="text-sm">${content}</p>
                    <p class="text-xs mt-1 opacity-75">${new Date().toLocaleString()}</p>
                </div>
            `;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</x-app-layout>
