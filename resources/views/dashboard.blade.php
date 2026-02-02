<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Active Tagging Rules</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $activeRules->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Recent Webhook Logs</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $recentLogs->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Success Rate</p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    {{ $recentLogs->count() > 0 ? round(($recentLogs->where('success', true)->count() / $recentLogs->count()) * 100) : 0 }}%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Tagging Rules -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Active Tagging Rules</h3>
                        <a href="{{ route('tagging-rules.index') }}" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                    </div>
                    @if($activeRules->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Store</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($activeRules->take(5) as $rule)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $rule->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $rule->store->name ?? '—' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('tagging-rules.edit', $rule) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No active tagging rules found.</p>
                    @endif
                </div>
            </div>

            <!-- Recent Webhook Logs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Webhook Logs</h3>
                        <a href="{{ route('tagging-rule-logs.index') }}" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                    </div>
                    @if($recentLogs->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rule</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tags Applied</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($recentLogs as $log)
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-600">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                            <td class="px-4 py-2 text-sm">{{ $log->taggingRule->name ?? '—' }}</td>
                                            <td class="px-4 py-2 text-sm">
                                                <span class="px-2 py-0.5 rounded text-xs {{ $log->source === 'webhook' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700' }}">
                                                    {{ $log->source ?? '—' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-sm">{{ $log->order_number ?? $log->order_id }}</td>
                                            <td class="px-4 py-2 text-sm">
                                                @if(!empty($log->tags_applied))
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach(array_slice($log->tags_applied, 0, 3) as $t)
                                                            <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">{{ $t }}</span>
                                                        @endforeach
                                                        @if(count($log->tags_applied) > 3)
                                                            <span class="text-xs text-gray-500">+{{ count($log->tags_applied) - 3 }} more</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2">
                                                @if($log->success)
                                                    <span class="text-green-600 text-sm font-medium">✓ OK</span>
                                                @else
                                                    <span class="text-red-600 text-sm font-medium" title="{{ $log->error_message }}">✗ Failed</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No webhook logs found yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
