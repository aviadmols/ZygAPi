<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Rule') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('tagging-rules.update', $taggingRule) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="store_id" class="block text-sm font-medium text-gray-700">Store</label>
                            <select name="store_id" id="store_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ $store->id == $taggingRule->store_id ? 'selected' : '' }}>{{ $store->name }}</option>
                                @endforeach
                            </select>
                            @error('store_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Rule Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $taggingRule->name) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $taggingRule->description) }}</textarea>
                        </div>

                        <!-- PHP Rule = main field that determines this rule -->
                        <div class="mb-4 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                            <label for="php_rule" class="block text-sm font-semibold text-gray-800">PHP Rule – this code defines the rule</label>
                            <p class="mt-1 text-xs text-gray-600 mb-2">The system runs this PHP for each order. Use <code>$order</code> (the order array) and set <code>$tags</code> (array of strings). <code>$shopDomain</code> and <code>$accessToken</code> are also available for Shopify API calls (e.g. curl). You may use <code>return;</code> to exit early. When this field has content, it is used; JSON and template below are ignored.</p>
                            <textarea name="php_rule" id="php_rule" rows="14"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                                placeholder="$tags = [];&#10;if (!empty($order['line_items'][0]['properties'])) { ... }">{{ old('php_rule', $taggingRule->php_rule) }}</textarea>
                        </div>

                        <!-- Optional: JSON + template (used only when PHP Rule is empty) -->
                        <details class="mb-4 border border-gray-200 rounded-lg">
                            <summary class="px-4 py-3 bg-gray-50 rounded-lg cursor-pointer text-sm font-medium text-gray-600">Optional: Rules JSON & Tags Template (used only when PHP Rule is empty)</summary>
                            <div class="p-4 space-y-4">
                                <div>
                                    <label for="rules_json" class="block text-sm font-medium text-gray-700">Rules JSON</label>
                                    <textarea name="rules_json" id="rules_json" rows="6"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm">{{ old('rules_json', is_array($taggingRule->rules_json) ? json_encode($taggingRule->rules_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (is_string($taggingRule->rules_json) ? $taggingRule->rules_json : '')) }}</textarea>
                                    @error('rules_json')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="tags_template" class="block text-sm font-medium text-gray-700">Tags Template</label>
                                    <textarea name="tags_template" id="tags_template" rows="4"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm">{{ old('tags_template', $taggingRule->tags_template) }}</textarea>
                                </div>
                            </div>
                        </details>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $taggingRule->is_active) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="overwrite_existing_tags" value="1" {{ old('overwrite_existing_tags', $taggingRule->overwrite_existing_tags) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Overwrite Existing Tags</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('tagging-rules.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update
                            </button>
                        </div>
                    </form>

                    <!-- Test Rule -->
                    <div class="mt-8 border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">Test Rule</h3>
                        <form id="test-rule-form">
                            @csrf
                            <div class="mb-4">
                                <label for="test_order_id" class="block text-sm font-medium text-gray-700">Order ID to Test</label>
                                <input type="text" name="order_id" id="test_order_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Test
                            </button>
                        </form>
                        <div id="test-results" class="mt-4 hidden"></div>

                        <!-- Debug log: request, full order JSON, tags returned -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <details class="bg-gray-100 rounded-lg border border-gray-300" id="test-log-details">
                                <summary class="px-4 py-3 cursor-pointer font-medium text-gray-700 select-none">Debug log – request, full order JSON, tags returned</summary>
                                <div class="p-4">
                                    <p class="text-xs text-gray-500 mb-2">After running Test, the log below shows the request, the full order JSON, and the tags returned.</p>
                                    <button type="button" id="test-log-clear" class="text-xs text-gray-600 hover:text-gray-800 underline mb-2">Clear log</button>
                                    <pre id="test-log" class="text-xs font-mono bg-gray-900 text-green-400 p-4 rounded overflow-x-auto max-h-[32rem] overflow-y-auto whitespace-pre-wrap break-words"></pre>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const testLogEl = document.getElementById('test-log');
        const testLogDetails = document.getElementById('test-log-details');
        document.getElementById('test-log-clear')?.addEventListener('click', () => { if (testLogEl) testLogEl.textContent = ''; });

        function appendTestLog(label, content) {
            if (!testLogEl) return;
            const ts = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const text = typeof content === 'string' ? content : JSON.stringify(content, null, 2);
            testLogEl.textContent += '[' + ts + '] ' + label + '\n' + text + '\n\n';
            testLogEl.scrollTop = testLogEl.scrollHeight;
            if (testLogDetails && !testLogDetails.open) testLogDetails.open = true;
        }

        document.getElementById('test-rule-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const orderId = document.getElementById('test_order_id').value.trim();
            const resultsDiv = document.getElementById('test-results');

            appendTestLog('Request (sent)', { order_id: orderId });

            try {
                const response = await fetch('{{ route("tagging-rules.test", $taggingRule) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ order_id: orderId })
                });

                const data = await response.json();

                if (data.success) {
                    appendTestLog('Response: tags returned', data.tags || []);
                    if (data.order) {
                        appendTestLog('Full order JSON', data.order);
                    }

                    resultsDiv.innerHTML = `
                        <div class="bg-green-100 border border-green-400 rounded-lg p-4">
                            <h4 class="font-semibold mb-2">Tags returned:</h4>
                            <div class="flex flex-wrap gap-2">
                                ${(data.tags || []).map(tag => `<span class="bg-blue-500 text-white px-2 py-1 rounded text-sm">${(tag || '').replace(/</g, '&lt;')}</span>`).join('')}
                            </div>
                            <p class="text-xs text-gray-600 mt-2">See Debug log below for full order JSON and request/response.</p>
                        </div>
                    `;
                    resultsDiv.classList.remove('hidden');
                } else {
                    appendTestLog('Response: error', data.error || 'Unknown error');
                    resultsDiv.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">${(data.error || '').replace(/</g, '&lt;')}</div>`;
                    resultsDiv.classList.remove('hidden');
                }
            } catch (error) {
                appendTestLog('Error', error.message);
                resultsDiv.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Error: ${(error.message || '').replace(/</g, '&lt;')}</div>`;
                resultsDiv.classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>
