<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Order Processing') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Process Orders Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">New Order Processing</h3>
                    <form id="process-orders-form">
                        @csrf
                        <div class="mb-4">
                            <label for="store_id" class="block text-sm font-medium text-gray-700">Store</label>
                            <select name="store_id" id="store_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select Store</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="rule_id" class="block text-sm font-medium text-gray-700">Rule (Optional - if not selected, all active rules will be applied)</label>
                            <select name="rule_id" id="rule_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Active Rules</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="order_ids" class="block text-sm font-medium text-gray-700">Order IDs (comma-separated)</label>
                            <textarea name="order_ids" id="order_ids" rows="5" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="123456789, 987654321, 456789123"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="overwrite_existing_tags" value="1"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Overwrite Existing Tags</span>
                            </label>
                        </div>

                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Start Processing
                        </button>
                    </form>
                </div>
            </div>

            <!-- Processing Jobs List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Processing Jobs</h3>
                    <div id="jobs-list">
                        @include('order-processing.jobs-list', ['jobs' => $jobs])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const processForm = document.getElementById('process-orders-form');
        const storeSelect = document.getElementById('store_id');
        const ruleSelect = document.getElementById('rule_id');

        function loadRulesForStore(storeId) {
            if (!storeId) {
                ruleSelect.innerHTML = '<option value="">All Active Rules</option>';
                return;
            }
            ruleSelect.innerHTML = '<option value="">All Active Rules</option>';
            fetch(`{{ url('tagging-rules') }}?store_id=${storeId}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load rules');
                    return response.json();
                })
                .then(rules => {
                    if (!Array.isArray(rules)) return;
                    rules.forEach(rule => {
                        const option = document.createElement('option');
                        option.value = rule.id;
                        option.textContent = rule.name + (rule.is_active ? ' (Active)' : '');
                        ruleSelect.appendChild(option);
                    });
                })
                .catch(err => {
                    console.error('Error loading rules:', err);
                });
        }

        storeSelect.addEventListener('change', () => loadRulesForStore(storeSelect.value));

        // Load rules on page load if store already selected
        if (storeSelect.value) loadRulesForStore(storeSelect.value);

        processForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(processForm);

            try {
                const response = await fetch('{{ route("orders.process") }}', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    alert('Processing started! Job ID: ' + data.job_id);
                    processForm.reset();
                    loadJobs();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Communication error: ' + error.message);
            }
        });

        function loadJobs() {
            fetch('{{ route("orders.process.index") }}')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('jobs-list').innerHTML = html;
                });
        }

        // Poll for progress updates
        setInterval(() => {
            document.querySelectorAll('[data-job-id]').forEach(el => {
                const jobId = el.getAttribute('data-job-id');
                fetch(`/orders/progress/${jobId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateJobProgress(jobId, data.progress);
                        }
                    });
            });
        }, 2000); // Poll every 2 seconds

        function updateJobProgress(jobId, progress) {
            const jobElement = document.querySelector(`[data-job-id="${jobId}"]`);
            if (!jobElement) return;

            const progressBar = jobElement.querySelector('.progress-bar');
            const progressText = jobElement.querySelector('.progress-text');
            
            if (progressBar) {
                progressBar.style.width = progress.progress_percentage + '%';
            }
            if (progressText) {
                progressText.textContent = `${progress.processed_orders}/${progress.total_orders} (${progress.progress_percentage}%)`;
            }
        }
    </script>
</x-app-layout>
