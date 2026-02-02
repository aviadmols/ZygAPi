<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Webhook / Tagging Logs') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-sm text-gray-600 mb-4">Log of all tagging rule invocations (webhook or dashboard). Time, order number, tags applied, success/error.</p>

                    <form method="get" class="flex flex-wrap gap-4 mb-6">
                        <div>
                            <label for="rule_id" class="block text-xs font-medium text-gray-500">Rule</label>
                            <select name="rule_id" id="rule_id" class="mt-1 rounded-md border-gray-300 text-sm">
                                <option value="">All rules</option>
                                @foreach(\App\Models\TaggingRule::with('store')->orderBy('name')->get() as $r)
                                    <option value="{{ $r->id }}" {{ request('rule_id') == $r->id ? 'selected' : '' }}>{{ $r->name }} ({{ $r->store->name ?? '' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="source" class="block text-xs font-medium text-gray-500">Source</label>
                            <select name="source" id="source" class="mt-1 rounded-md border-gray-300 text-sm">
                                <option value="">All</option>
                                <option value="webhook" {{ request('source') === 'webhook' ? 'selected' : '' }}>Webhook</option>
                                <option value="dashboard" {{ request('source') === 'dashboard' ? 'selected' : '' }}>Dashboard</option>
                                <option value="api" {{ request('source') === 'api' ? 'selected' : '' }}>API</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded text-sm">Filter</button>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rule</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tags applied</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($logs as $log)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-600">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $log->taggingRule->name ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm"><span class="px-2 py-0.5 rounded text-xs {{ $log->source === 'webhook' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700' }}">{{ $log->source ?? '—' }}</span></td>
                                        <td class="px-4 py-2 text-sm font-mono">{{ $log->order_id }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $log->order_number ?? '—' }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            @if(!empty($log->tags_applied))
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($log->tags_applied as $t)
                                                        <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs">{{ $t }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2">
                                            @if($log->success)
                                                <span class="text-green-600 text-sm font-medium">OK</span>
                                            @else
                                                <span class="text-red-600 text-sm font-medium" title="{{ $log->error_message }}">Failed</span>
                                                @if($log->error_message)
                                                    <p class="text-xs text-red-500 max-w-xs truncate" title="{{ $log->error_message }}">{{ $log->error_message }}</p>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 text-sm">No logs yet. Invoke a rule via webhook or dashboard to see entries here.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($logs->hasPages())
                        <div class="mt-4">
                            {{ $logs->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
