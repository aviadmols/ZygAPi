<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Custom Endpoint – Choose Store</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">Select the store for this endpoint. Next you will define the prompt, platform (Shopify or Recharge), input parameters, and expected return values.</p>
                    <form method="GET" action="{{ route('custom-endpoints.build', ['store' => '__ID__']) }}" id="store-form">
                        <div class="mb-4">
                            <label for="store_id" class="block text-sm font-medium text-gray-700">Store</label>
                            <select name="store_id" id="store_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select a store</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">Next</button>
                    </form>
                    <script>
                        document.getElementById('store-form').addEventListener('submit', function(e) {
                            e.preventDefault();
                            var id = document.getElementById('store_id').value;
                            if (!id) return;
                            this.action = this.action.replace('__ID__', id);
                            window.location.href = this.action;
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
