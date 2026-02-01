<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('עריכת חוקיות') }}
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
                            <label for="store_id" class="block text-sm font-medium text-gray-700">חנות</label>
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
                            <label for="name" class="block text-sm font-medium text-gray-700">שם החוקיות</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $taggingRule->name) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700">תיאור</label>
                            <textarea name="description" id="description" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $taggingRule->description) }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label for="tags_template" class="block text-sm font-medium text-gray-700">תבנית תגיות</label>
                            <textarea name="tags_template" id="tags_template" rows="5"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm">{{ old('tags_template', $taggingRule->tags_template) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">תבנית תגיות עם ביטויים. לדוגמה: {{switch(...)}}, {{get(split(...))}}</p>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $taggingRule->is_active) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="mr-2 text-sm text-gray-700">פעיל</span>
                            </label>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="overwrite_existing_tags" value="1" {{ old('overwrite_existing_tags', $taggingRule->overwrite_existing_tags) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="mr-2 text-sm text-gray-700">דרוס תגיות קיימות</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('tagging-rules.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">ביטול</a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                עדכן
                            </button>
                        </div>
                    </form>

                    <!-- Test Rule -->
                    <div class="mt-8 border-t pt-6">
                        <h3 class="text-lg font-semibold mb-4">בדיקת חוקיות</h3>
                        <form id="test-rule-form">
                            @csrf
                            <div class="mb-4">
                                <label for="test_order_id" class="block text-sm font-medium text-gray-700">מספר הזמנה לבדיקה</label>
                                <input type="text" name="order_id" id="test_order_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                בדוק
                            </button>
                        </form>
                        <div id="test-results" class="mt-4 hidden"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('test-rule-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const orderId = document.getElementById('test_order_id').value;
            const resultsDiv = document.getElementById('test-results');

            try {
                const response = await fetch('{{ route("tagging-rules.test", $taggingRule) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ order_id: orderId })
                });

                const data = await response.json();
                if (data.success) {
                    resultsDiv.innerHTML = `
                        <div class="bg-green-100 border border-green-400 rounded-lg p-4">
                            <h4 class="font-semibold mb-2">תגיות שנוצרו:</h4>
                            <div class="flex flex-wrap gap-2">
                                ${data.tags.map(tag => `<span class="bg-blue-500 text-white px-2 py-1 rounded text-sm">${tag}</span>`).join('')}
                            </div>
                        </div>
                    `;
                    resultsDiv.classList.remove('hidden');
                } else {
                    resultsDiv.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">${data.error}</div>`;
                    resultsDiv.classList.remove('hidden');
                }
            } catch (error) {
                resultsDiv.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">שגיאה: ${error.message}</div>`;
                resultsDiv.classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>
