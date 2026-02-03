<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Build Custom Endpoint – {{ $store->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">1. Define your endpoint</h3>
                        <div class="mb-4">
                            <label for="platform" class="block text-sm font-medium text-gray-700">Platform</label>
                            <select id="platform" name="platform" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="shopify">Shopify</option>
                                <option value="recharge">Recharge</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">AI will use this platform's API documentation to generate the code.</p>
                        </div>
                        <div class="mb-4">
                            <label for="prompt" class="block text-sm font-medium text-gray-700">Prompt (what should the endpoint do?)</label>
                            <textarea id="prompt" name="prompt" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Fetch order by order_id, then update order tags based on line items..."></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Input parameters (what the endpoint receives)</label>
                            <p class="text-xs text-gray-500 mb-2">Add parameter names the endpoint will receive (e.g. order_id, subscription_id).</p>
                            <div id="input-params-container" class="space-y-2">
                                <div class="input-param-row flex gap-2 items-center">
                                    <input type="text" name="input_param_name[]" placeholder="e.g. order_id" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm flex-1">
                                    <button type="button" class="remove-input text-red-600 text-sm">Remove</button>
                                </div>
                            </div>
                            <button type="button" id="add-input-param" class="mt-2 text-sm text-indigo-600 hover:text-indigo-800">+ Add parameter</button>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Test return values (expected response keys and sample values)</label>
                            <p class="text-xs text-gray-500 mb-2">Define the keys and example values the endpoint should return (for AI to match).</p>
                            <div id="return-values-container" class="space-y-2">
                                <div class="return-row flex gap-2 items-center">
                                    <input type="text" name="return_name[]" placeholder="Key name" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm w-40">
                                    <input type="text" name="return_value[]" placeholder="Example value" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm flex-1">
                                    <button type="button" class="remove-return text-red-600 text-sm">Remove</button>
                                </div>
                            </div>
                            <button type="button" id="add-return-value" class="mt-2 text-sm text-indigo-600 hover:text-indigo-800">+ Add return field</button>
                        </div>
                        <button type="button" id="generate-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">Generate code</button>
                    </div>

                    <div id="generated-section" class="hidden mb-6 p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">2. Generated code – Save as endpoint</h3>
                        <textarea id="php_code" name="php_code" rows="16" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm mb-4"></textarea>
                        <input type="hidden" id="http_method" value="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="ep_name" class="block text-sm font-medium text-gray-700">Endpoint name</label>
                                <input type="text" id="ep_name" name="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="ep_slug" class="block text-sm font-medium text-gray-700">Slug (URL path)</label>
                                <input type="text" id="ep_slug" name="slug" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="my-endpoint">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="webhook_token" class="block text-sm font-medium text-gray-700">Webhook token (optional; if set, required for calls)</label>
                            <input type="text" id="webhook_token" name="webhook_token" maxlength="64" class="mt-1 block w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm">
                        </div>
                        <form id="save-form" method="POST" action="{{ route('custom-endpoints.store') }}">
                            @csrf
                            <input type="hidden" name="store_id" value="{{ $store->id }}">
                            <input type="hidden" name="platform" id="save_platform">
                            <input type="hidden" name="prompt" id="save_prompt">
                            <input type="hidden" name="input_params" id="save_input_params">
                            <input type="hidden" name="test_return_values" id="save_test_return_values">
                            <input type="hidden" name="php_code" id="save_php_code">
                            <input type="hidden" name="name" id="save_name">
                            <input type="hidden" name="slug" id="save_slug">
                            <input type="hidden" name="webhook_token" id="save_webhook_token">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Save endpoint</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const storeId = {{ $store->id }};
        const generateUrl = '{{ route("custom-endpoints.generate") }}';
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        document.getElementById('add-input-param').addEventListener('click', function() {
            const div = document.createElement('div');
            div.className = 'input-param-row flex gap-2 items-center';
            div.innerHTML = '<input type="text" name="input_param_name[]" placeholder="e.g. order_id" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm flex-1"><button type="button" class="remove-input text-red-600 text-sm">Remove</button>';
            div.querySelector('.remove-input').addEventListener('click', function() { div.remove(); });
            document.getElementById('input-params-container').appendChild(div);
        });
        document.querySelectorAll('.remove-input').forEach(function(btn) {
            btn.addEventListener('click', function() { this.closest('.input-param-row').remove(); });
        });

        document.getElementById('add-return-value').addEventListener('click', function() {
            const div = document.createElement('div');
            div.className = 'return-row flex gap-2 items-center';
            div.innerHTML = '<input type="text" name="return_name[]" placeholder="Key name" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm w-40"><input type="text" name="return_value[]" placeholder="Example value" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm flex-1"><button type="button" class="remove-return text-red-600 text-sm">Remove</button>';
            div.querySelector('.remove-return').addEventListener('click', function() { div.remove(); });
            document.getElementById('return-values-container').appendChild(div);
        });
        document.querySelectorAll('.remove-return').forEach(function(btn) {
            btn.addEventListener('click', function() { this.closest('.return-row').remove(); });
        });

        document.getElementById('generate-btn').addEventListener('click', async function() {
            const btn = this;
            const prompt = document.getElementById('prompt').value.trim();
            if (!prompt) { alert('Enter a prompt.'); return; }
            const inputNames = Array.from(document.querySelectorAll('input[name="input_param_name[]"]')).map(i => i.value.trim()).filter(Boolean);
            const returnNames = Array.from(document.querySelectorAll('input[name="return_name[]"]')).map(i => i.value.trim()).filter(Boolean);
            const returnValues = Array.from(document.querySelectorAll('input[name="return_value[]"]')).map(i => i.value.trim());
            const returnList = returnNames.map((n, i) => ({ name: n, value: returnValues[i] || '' }));

            btn.disabled = true;
            btn.textContent = 'Generating...';
            try {
                const res = await fetch(generateUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({
                        store_id: storeId,
                        platform: document.getElementById('platform').value,
                        prompt: prompt,
                        input_params: inputNames.map(n => ({ name: n })),
                        test_return_values: returnList
                    })
                });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('php_code').value = data.php_code;
                    document.getElementById('http_method').value = data.http_method || 'POST';
                    document.getElementById('generated-section').classList.remove('hidden');
                } else {
                    alert('Error: ' + (data.error || 'Unknown'));
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
            btn.disabled = false;
            btn.textContent = 'Generate code';
        });

        document.getElementById('save-form').addEventListener('submit', function(e) {
            const name = document.getElementById('ep_name').value.trim();
            const slug = document.getElementById('ep_slug').value.trim() || name.toLowerCase().replace(/\s+/g, '-');
            if (!name) { e.preventDefault(); alert('Enter endpoint name.'); return; }
            document.getElementById('save_name').value = name;
            document.getElementById('save_slug').value = slug;
            document.getElementById('save_platform').value = document.getElementById('platform').value;
            document.getElementById('save_prompt').value = document.getElementById('prompt').value;
            const inputNames = Array.from(document.querySelectorAll('input[name="input_param_name[]"]')).map(i => i.value.trim()).filter(Boolean);
            document.getElementById('save_input_params').value = JSON.stringify(inputNames.map(n => ({ name: n })));
            const returnNames = Array.from(document.querySelectorAll('input[name="return_name[]"]')).map(i => i.value.trim()).filter(Boolean);
            const returnValues = Array.from(document.querySelectorAll('input[name="return_value[]"]')).map(i => i.value.trim());
            document.getElementById('save_test_return_values').value = JSON.stringify(returnNames.map((n, i) => ({ name: n, value: returnValues[i] || '' })));
            document.getElementById('save_php_code').value = document.getElementById('php_code').value;
            document.getElementById('save_webhook_token').value = document.getElementById('webhook_token').value;
        });
    </script>
</x-app-layout>
