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

                    <!-- Order Data Input -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sample Order (JSON) - Optional</label>
                        <textarea id="order-data" rows="5"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                            placeholder='{"id": "123", "line_items": [...]}'></textarea>
                        <p class="mt-1 text-xs text-gray-500">Paste order JSON, or leave empty and use Order number below to fetch.</p>
                    </div>

                    <!-- Test with order number -->
                    @if(count($aiConversation->messages ?? []) > 0)
                        <div class="mb-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">Test with order number</h3>
                            <p class="text-xs text-gray-600 mb-3">Enter an order ID or order number to see which tags the AI would apply (generates PHP from this conversation and runs it on that order).</p>
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
                            <p class="text-sm text-gray-600 mb-2">Generate a PHP rule from this conversation and save it to Tagging Rules. You can then edit and run it there.</p>
                            <div class="flex flex-wrap items-center gap-4">
                                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Generate Rule from Conversation
                                </button>
                                <span class="text-xs text-gray-500">Requires: Sample Order (JSON above) or Order number below.</span>
                            </div>
                            <div class="mt-2">
                                <label for="generate-order-id" class="block text-sm font-medium text-gray-700 mb-1">Or fetch sample by order number (optional)</label>
                                <input type="text" id="generate-order-id" placeholder="Order ID or number to use as sample"
                                    class="block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            </div>
                        </form>
                    @endif

                    @if($aiConversation->generatedRule)
                        <div class="mt-4 p-4 bg-green-100 border border-green-400 rounded-lg">
                            <p class="text-sm text-green-800">Rule generated successfully!</p>
                            <a href="{{ route('tagging-rules.edit', $aiConversation->generatedRule) }}" class="text-blue-600 hover:text-blue-800">Edit Rule</a>
                        </div>
                    @endif
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

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            // Add user message to UI
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
                    body: JSON.stringify({
                        message: message,
                        order_data: orderDataInput.value || null
                    })
                });

                const data = await response.json();
                if (data.success) {
                    addMessage('assistant', data.message);
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                alert('Communication error: ' + error.message);
            }
        });

        if (generateRuleForm) {
            generateRuleForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const orderData = orderDataInput.value.trim();
                const orderIdForSample = document.getElementById('generate-order-id')?.value?.trim() || null;
                if (!orderData && !orderIdForSample) {
                    alert('Please enter Sample Order (JSON) or an Order number to fetch as sample.');
                    return;
                }

                const userMessages = Array.from(chatMessages.querySelectorAll('[data-role="user"]'))
                    .map(el => el.textContent.trim())
                    .join('\n');
                document.getElementById('user-requirements').value = userMessages;

                try {
                    const response = await fetch(`{{ route('ai-conversations.generate-rule', $aiConversation) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            order_data: orderData || null,
                            order_id: orderIdForSample,
                            user_requirements: userMessages
                        })
                    });

                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        alert('Server error (received HTML instead of JSON). Set APP_DEBUG=true on Railway to see details. Status: ' + response.status);
                        return;
                    }

                    const data = await response.json();
                    if (data.success) {
                        alert('Rule generated successfully! You can edit and test it in Tagging Rules.');
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.error || data.message || 'Unknown error'));
                    }
                } catch (error) {
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
                const userMessages = Array.from(chatMessages.querySelectorAll('[data-role="user"]'))
                    .map(el => el.textContent.trim())
                    .join('\n');
                if (!userMessages) {
                    testOrderResults.innerHTML = '<p class="text-amber-600 text-sm">Send at least one message describing what to check and which tags to return.</p>';
                    testOrderResults.classList.remove('hidden');
                    return;
                }
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
                        body: JSON.stringify({
                            order_id: orderId,
                            order_data: orderDataInput.value.trim() || null
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        const tags = data.tags || [];
                        let html = '<p class="text-sm font-medium text-gray-700 mb-2">Tags that would be applied:</p>';
                        if (tags.length) {
                            html += '<div class="flex flex-wrap gap-2 mb-3">' + tags.map(t => '<span class="bg-blue-500 text-white px-2 py-1 rounded text-sm">' + (t || '').replace(/</g, '&lt;') + '</span>').join('') + '</div>';
                        } else {
                            html += '<p class="text-gray-500 text-sm mb-3">No tags.</p>';
                        }
                        if (data.php_code) {
                            html += '<details class="mt-2"><summary class="text-sm cursor-pointer text-gray-600">Show generated PHP</summary><pre class="mt-2 p-2 bg-gray-100 rounded text-xs overflow-x-auto">' + (data.php_code || '').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</pre></details>';
                        }
                        testOrderResults.innerHTML = html;
                    } else {
                        testOrderResults.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm">' + (data.error || 'Error') + '</div>';
                    }
                } catch (err) {
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
