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
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Prompt</label>
                        <p class="mt-1 text-sm text-gray-600">{{ $customEndpoint->prompt }}</p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">PHP code</label>
                        <pre class="mt-1 p-4 bg-gray-900 text-green-400 rounded text-xs overflow-x-auto max-h-96 overflow-y-auto">{{ $customEndpoint->php_code }}</pre>
                    </div>
                    <a href="{{ route('custom-endpoints.index') }}" class="text-gray-600 hover:text-gray-900">Back to list</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
