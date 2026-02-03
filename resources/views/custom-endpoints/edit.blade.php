<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Custom Endpoint</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('custom-endpoints.update', $customEndpoint) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label for="store_id" class="block text-sm font-medium text-gray-700">Store</label>
                            <select name="store_id" id="store_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ $store->id == $customEndpoint->store_id ? 'selected' : '' }}>{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $customEndpoint->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="mb-4">
                            <label for="slug" class="block text-sm font-medium text-gray-700">Slug</label>
                            <input type="text" name="slug" id="slug" value="{{ old('slug', $customEndpoint->slug) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono">
                        </div>
                        <div class="mb-4">
                            <label for="platform" class="block text-sm font-medium text-gray-700">Platform</label>
                            <select name="platform" id="platform" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="shopify" {{ $customEndpoint->platform === 'shopify' ? 'selected' : '' }}>Shopify</option>
                                <option value="recharge" {{ $customEndpoint->platform === 'recharge' ? 'selected' : '' }}>Recharge</option>
                            </select>
                        </div>
                        <input type="hidden" name="prompt" value="{{ old('prompt', $customEndpoint->prompt) }}">
                        <div class="mb-4">
                            <label for="php_code" class="block text-sm font-medium text-gray-700">PHP code</label>
                            <textarea name="php_code" id="php_code" rows="14" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm">{{ old('php_code', $customEndpoint->php_code) }}</textarea>
                        </div>
                        <div class="mb-4">
                            <label for="webhook_token" class="block text-sm font-medium text-gray-700">Webhook token</label>
                            <input type="text" name="webhook_token" id="webhook_token" value="{{ old('webhook_token', $customEndpoint->webhook_token) }}" maxlength="64" class="mt-1 block w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm">
                        </div>
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $customEndpoint->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>
                        <div class="flex items-center gap-4">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Update</button>
                            <a href="{{ route('custom-endpoints.show', $customEndpoint) }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
