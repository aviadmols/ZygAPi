<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New Rule') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">For creating complex rules with AI, use the <a href="{{ route('ai-conversations.create') }}" class="text-blue-600">AI Chat Interface</a></p>

                    @if($stores->isEmpty())
                        <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg text-amber-800 text-sm">
                            No stores yet. <a href="{{ route('stores.create') }}" class="font-medium underline">Add a store</a> first, then create a rule.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tagging-rules.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="store_id" class="block text-sm font-medium text-gray-700">Store</label>
                            <select name="store_id" id="store_id" {{ $stores->isEmpty() ? 'disabled' : 'required' }}
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select Store</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                            @error('store_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700">Rule Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label for="rules_json" class="block text-sm font-medium text-gray-700">Rules JSON (optional)</label>
                            <p class="mt-1 text-xs text-gray-500 mb-1">Paste the rules JSON here (e.g. from AI). Structure: conditions, tags, tags_template.</p>
                            <textarea name="rules_json" id="rules_json" rows="8"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                                placeholder='{"conditions":[{"field":"0.Days","operator":"equals","value":"14"}],"tags":["A"],"tags_template":""}'>{{ old('rules_json') }}</textarea>
                            @error('rules_json')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="tags_template" class="block text-sm font-medium text-gray-700">Tags Template</label>
                            <textarea name="tags_template" id="tags_template" rows="5"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                                placeholder="e.g. switch(0.Days-0.Gram; 14D-50; A)">{{ old('tags_template') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Tag template with expressions. Use switch, get, split inside double curly braces.</p>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', false) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="overwrite_existing_tags" value="1" {{ old('overwrite_existing_tags', false) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Overwrite Existing Tags</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('tagging-rules.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</a>
                            <button type="submit" {{ $stores->isEmpty() ? 'disabled' : '' }} class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed">
                                Save
                            </button>
                        </div>
                    </form>

                    <!-- Test rule (preview tags for an order before saving) -->
                    @if(!$stores->isEmpty())
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-semibold mb-2">Test rule</h3>
                            <p class="text-sm text-gray-600 mb-4">Enter an order number to see which tags would be applied with the current rule (Rules JSON + Tags Template above).</p>
                            <div class="flex flex-wrap items-end gap-4">
                                <div class="flex-1 min-w-[200px]">
                                    <label for="preview_order_id" class="block text-sm font-medium text-gray-700 mb-1">Order number</label>
                                    <input type="text" id="preview_order_id" placeholder="e.g. 1234567890"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <button type="button" id="preview-tags-btn" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Test
                                </button>
                            </div>
                            <div id="preview-results" class="mt-4 hidden"></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('preview-tags-btn')?.addEventListener('click', async function() {
            const orderId = document.getElementById('preview_order_id').value.trim();
            const storeId = document.getElementById('store_id').value;
            const resultsDiv = document.getElementById('preview-results');
            if (!orderId) {
                resultsDiv.innerHTML = '<p class="text-red-600 text-sm">Please enter an order number.</p>';
                resultsDiv.classList.remove('hidden');
                return;
            }
            if (!storeId) {
                resultsDiv.innerHTML = '<p class="text-red-600 text-sm">Please select a store first.</p>';
                resultsDiv.classList.remove('hidden');
                return;
            }
            resultsDiv.innerHTML = '<p class="text-gray-500 text-sm">Loading...</p>';
            resultsDiv.classList.remove('hidden');
            try {
                const response = await fetch('{{ route("tagging-rules.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        store_id: storeId,
                        order_id: orderId,
                        rules_json: document.getElementById('rules_json').value.trim() || null,
                        tags_template: document.getElementById('tags_template').value.trim() || null
                    })
                });
                const data = await response.json();
                if (data.success) {
                    const tags = data.tags || [];
                    resultsDiv.innerHTML = '<p class="text-sm font-medium text-gray-700 mb-2">Tags that would be applied:</p>' +
                        (tags.length ? '<div class="flex flex-wrap gap-2">' + tags.map(t => '<span class="bg-blue-500 text-white px-2 py-1 rounded text-sm">' + (t || '').replace(/</g, '&lt;') + '</span>').join('') + '</div>' : '<p class="text-gray-500 text-sm">No tags (conditions may not match or template is empty).</p>');
                } else {
                    resultsDiv.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm">' + (data.error || 'Error') + '</div>';
                }
            } catch (err) {
                resultsDiv.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm">Error: ' + err.message + '</div>';
            }
        });
    </script>
</x-app-layout>
