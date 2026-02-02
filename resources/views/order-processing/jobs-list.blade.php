@php
    $jobsList = $jobs->items();
    $latestJob = $jobsList[0] ?? null;
    $olderJobs = array_slice($jobsList, 1);
@endphp

<div class="space-y-4">
    @if($latestJob)
        {{-- Show only the latest (most recent) job by default --}}
        @include('order-processing.job-card', ['job' => $latestJob])

        @if(count($olderJobs) > 0)
            <details class="group mt-4" id="more-jobs-details">
                <summary class="cursor-pointer list-none flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800 py-2">
                    More ({{ count($olderJobs) }} {{ count($olderJobs) === 1 ? 'job' : 'jobs' }})
                </summary>
                <div class="space-y-4 mt-2 pl-4 border-l-2 border-gray-200">
                    @foreach($olderJobs as $job)
                        @include('order-processing.job-card', ['job' => $job])
                    @endforeach
                </div>
            </details>
        @endif
    @else
        <p class="text-gray-500 text-center py-8">No processing jobs</p>
    @endif

    {{ $jobs->links() }}
</div>
