<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $customEndpoint->name }}</h2>
            <a href="{{ route('custom-endpoints.edit', $customEndpoint) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-gray-500 mb-2">Store: <strong>{{ $customEndpoint->store->name }}</strong> · Platform: <span class="px-2 py-0.5 rounded text-xs {{ $customEndpoint->platform === 'recharge' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">{{ $customEndpoint->platform }}</span></p>
                    <div class="mb-6 p-4 bg-amber-50 rounded-lg border border-amber-200">
                        <h4 class="text-sm font-semibold text-gray-800 mb-2">Endpoint URL</h4>
                        <p class="text-xs font-mono bg-white border border-gray-200 rounded px-2 py-2 break-all">{{ url('webhooks/custom-endpoint/' . $customEndpoint->slug) }}</p>
                        <p class="text-xs text-gray-500 mt-2">Method: POST (or PUT if your code sets $httpMethod). Send JSON body with your input parameters. Auth: set header <code>X-Webhook-Token</code> or <code>?token=</code> to the webhook token (if configured).</p>
                    </div>
                    @if($customEndpoint->description)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <p class="mt-1 text-sm text-gray-600 whitespace-pre-line">{{ $customEndpoint->description }}</p>
                        </div>
                    @endif
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700">PHP code</label>
                        <pre class="mt-1 p-4 bg-gray-900 text-green-400 rounded text-xs overflow-x-auto max-h-96 overflow-y-auto">{{ $customEndpoint->php_code }}</pre>
                    </div>

                    <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">Test endpoint (Send)</h4>
                        <p class="text-xs text-gray-500 mb-3">Fill the input parameters and click Send to run the endpoint once and see a detailed log.</p>
                        <form id="test-endpoint-form" class="space-y-3">
                            @csrf
                            @php
                                $inputParams = $customEndpoint->input_params ?? [];
                            @endphp
                            @forelse($inputParams as $param)
                                @php
                                    $pName = is_array($param) ? ($param['name'] ?? '') : $param;
                                @endphp
                                @if($pName)
                                    <div>
                                        <label for="test_{{ $pName }}" class="block text-xs font-medium text-gray-700">{{ $pName }}</label>
                                        <input type="text" id="test_{{ $pName }}" name="input[{{ $pName }}]" placeholder="e.g. order ID or subscription ID" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    </div>
                                @endif
                            @empty
                                <p class="text-xs text-gray-500">No input parameters defined. You can still Send to run with empty input.</p>
                            @endforelse
                            <button type="submit" id="test-send-btn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm mt-2">Send</button>
                        </form>
                        <div id="test-result" class="mt-4 hidden"></div>
                        <div id="test-log-section" class="mt-4 hidden">
                            <h5 class="text-xs font-semibold text-gray-700 mb-2">Detailed log</h5>
                            <pre id="test-log-content" class="text-xs font-mono bg-gray-900 text-green-400 p-4 rounded overflow-x-auto max-h-96 overflow-y-auto whitespace-pre-wrap"></pre>
                        </div>
                    </div>

                    <a href="{{ route('custom-endpoints.index') }}" class="text-gray-600 hover:text-gray-900">Back to list</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('test-endpoint-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            var form = this;
            var btn = document.getElementById('test-send-btn');
            var resultDiv = document.getElementById('test-result');
            var logSection = document.getElementById('test-log-section');
            var logContent = document.getElementById('test-log-content');
            var input = {};
            form.querySelectorAll('input[name^="input["]').forEach(function(inp) {
                var name = inp.name.match(/input\[([^\]]+)\]/);
                if (name) input[name[1]] = inp.value;
            });
            btn.disabled = true;
            btn.textContent = 'Sending...';
            resultDiv.classList.add('hidden');
            logSection.classList.add('hidden');
            try {
                var res = await fetch('{{ route("custom-endpoints.test", $customEndpoint) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ input: input })
                });
                var data = await res.json();
                var logText = (data.log || []).map(function(entry) {
                    return '[' + (entry.timestamp || '') + '] ' + (entry.step || '') + '\n' + JSON.stringify(entry, null, 2);
                }).join('\n\n');
                logContent.textContent = logText || JSON.stringify(data, null, 2);
                logSection.classList.remove('hidden');
                if (data.success) {
                    resultDiv.innerHTML = '<div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded text-sm">Success. Response: <pre class="mt-2 p-2 bg-white rounded text-xs overflow-x-auto">' + (JSON.stringify(data.response, null, 2) || '{}').replace(/</g, '&lt;') + '</pre></div>';
                } else {
                    resultDiv.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded text-sm">Error: ' + (data.error || 'Unknown').replace(/</g, '&lt;') + '</div>';
                }
                resultDiv.classList.remove('hidden');
            } catch (err) {
                logContent.textContent = 'Request failed: ' + err.message;
                logSection.classList.remove('hidden');
                resultDiv.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded text-sm">Error: ' + err.message.replace(/</g, '&lt;') + '</div>';
                resultDiv.classList.remove('hidden');
            }
            btn.disabled = false;
            btn.textContent = 'Send';
        });
    </script>
</x-app-layout>
