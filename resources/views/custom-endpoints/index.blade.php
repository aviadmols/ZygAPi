<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Custom Endpoints
            </h2>
            <a href="{{ route('custom-endpoints.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                Create Endpoint
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">Build your own endpoint with AI: choose store, describe the action, define inputs and expected returns. The AI generates the code and you get a webhook URL.</p>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Store</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($endpoints as $ep)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $ep->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $ep->store->name ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm"><span class="px-2 py-0.5 rounded text-xs {{ $ep->platform === 'recharge' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">{{ $ep->platform }}</span></td>
                                    <td class="px-6 py-4 text-sm font-mono">{{ $ep->slug }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <a href="{{ route('custom-endpoints.show', $ep) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                        <a href="{{ route('custom-endpoints.edit', $ep) }}" class="text-gray-600 hover:text-gray-900 ml-3">Edit</a>
                                        <form action="{{ route('custom-endpoints.destroy', $ep) }}" method="POST" class="inline ml-3">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this endpoint?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">No custom endpoints yet. <a href="{{ route('custom-endpoints.create') }}" class="text-indigo-600">Create one</a>.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if($endpoints->hasPages())
                        <div class="mt-4">{{ $endpoints->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
