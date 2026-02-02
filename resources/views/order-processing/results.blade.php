<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Order Processing Results') }} #{{ $job->id }}
            </h2>
            <a href="{{ route('orders.process.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">Back to Order Processing</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <p class="text-sm text-gray-600"><strong>Store:</strong> {{ $job->store->name ?? '—' }}</p>
                        @if($job->rule)
                            <p class="text-sm text-gray-600"><strong>Rule:</strong> {{ $job->rule->name }}</p>
                        @else
                            <p class="text-sm text-gray-600"><strong>Rule:</strong> All active rules</p>
                        @endif
                        <p class="text-sm text-gray-600 mt-1">
                            <strong>Status:</strong>
                            <span class="px-2 py-0.5 text-xs rounded-full {{ $job->status === 'completed' ? 'bg-green-100 text-green-800' : ($job->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($job->status ?? 'pending') }}
                            </span>
                        </p>
                        <p class="text-sm text-gray-600"><strong>Progress:</strong> {{ $job->processed_orders ?? 0 }}/{{ $job->total_orders ?? 0 }} processed, {{ $job->failed_orders ?? 0 }} failed</p>
                        @if($job->started_at)
                            <p class="text-xs text-gray-500 mt-1">Started: {{ $job->started_at->format('Y-m-d H:i:s') }}</p>
                        @endif
                        @if($job->completed_at)
                            <p class="text-xs text-gray-500">Completed: {{ $job->completed_at->format('Y-m-d H:i:s') }}</p>
                        @endif
                    </div>

                    <h3 class="text-lg font-semibold mb-2">Details</h3>
                    @php
                        $progress = $job->progress ?? [];
                    @endphp
                    @if(!empty($progress))
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Processed at</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($progress as $item)
                                        <tr>
                                            <td class="px-4 py-2 text-sm">{{ $item['order_id'] ?? '—' }}</td>
                                            <td class="px-4 py-2">
                                                @if(!empty($item['success']))
                                                    <span class="text-green-600 text-sm">OK</span>
                                                @else
                                                    <span class="text-red-600 text-sm">Failed</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-500">{{ $item['processed_at'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No details yet. The job may still be pending or processing.</p>
                    @endif

                    <div class="mt-6">
                        <a href="{{ route('orders.process.index') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Back to Order Processing
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
