<div class="space-y-4">
    @forelse($jobs as $job)
        <div data-job-id="{{ $job->id }}" class="border border-gray-200 rounded-lg p-4">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h4 class="font-semibold">עבודה #{{ $job->id }}</h4>
                    <p class="text-sm text-gray-600">חנות: {{ $job->store->name }}</p>
                    @if($job->rule)
                        <p class="text-sm text-gray-600">חוקיות: {{ $job->rule->name }}</p>
                    @endif
                </div>
                <span class="px-2 py-1 text-xs rounded-full {{ $job->status === 'completed' ? 'bg-green-100 text-green-800' : ($job->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                    {{ $job->status === 'completed' ? 'הושלם' : ($job->status === 'failed' ? 'נכשל' : ($job->status === 'processing' ? 'מעבד' : 'ממתין')) }}
                </span>
            </div>

            <div class="mb-2">
                <div class="flex justify-between text-sm mb-1">
                    <span class="progress-text">{{ $job->processed_orders }}/{{ $job->total_orders }} ({{ $job->total_orders > 0 ? round(($job->processed_orders + $job->failed_orders) / $job->total_orders * 100, 2) : 0 }}%)</span>
                    <span>נכשלו: {{ $job->failed_orders }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="progress-bar bg-blue-600 h-2 rounded-full transition-all duration-300" 
                         style="width: {{ $job->total_orders > 0 ? round(($job->processed_orders + $job->failed_orders) / $job->total_orders * 100, 2) : 0 }}%"></div>
                </div>
            </div>

            @if($job->started_at)
                <p class="text-xs text-gray-500">התחיל: {{ $job->started_at->format('Y-m-d H:i:s') }}</p>
            @endif
            @if($job->completed_at)
                <p class="text-xs text-gray-500">הושלם: {{ $job->completed_at->format('Y-m-d H:i:s') }}</p>
            @endif

            <a href="{{ route('orders.results', $job) }}" class="text-blue-600 hover:text-blue-800 text-sm">צפה בתוצאות</a>
        </div>
    @empty
        <p class="text-gray-500 text-center py-8">אין עבודות עיבוד</p>
    @endforelse

    {{ $jobs->links() }}
</div>
