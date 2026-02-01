<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('עיבוד הזמנות') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Process Orders Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">עיבוד הזמנות חדש</h3>
                    <form id="process-orders-form">
                        @csrf
                        <div class="mb-4">
                            <label for="store_id" class="block text-sm font-medium text-gray-700">חנות</label>
                            <select name="store_id" id="store_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">בחר חנות</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="rule_id" class="block text-sm font-medium text-gray-700">חוקיות (אופציונלי - אם לא נבחר, כל החוקיות הפעילות יופעלו)</label>
                            <select name="rule_id" id="rule_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">כל החוקיות הפעילות</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="order_ids" class="block text-sm font-medium text-gray-700">מספרי הזמנות (מופרדים בפסיק)</label>
                            <textarea name="order_ids" id="order_ids" rows="5" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="123456789, 987654321, 456789123"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="overwrite_existing_tags" value="1"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="mr-2 text-sm text-gray-700">דרוס תגיות קיימות</span>
                            </label>
                        </div>

                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            התחל עיבוד
                        </button>
                    </form>
                </div>
            </div>

            <!-- Processing Jobs List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">עבודות עיבוד</h3>
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

        // Load rules when store changes
        storeSelect.addEventListener('change', async () => {
            const storeId = storeSelect.value;
            if (!storeId) {
                ruleSelect.innerHTML = '<option value="">כל החוקיות הפעילות</option>';
                return;
            }

            try {
                const response = await fetch(`/tagging-rules?store_id=${storeId}`);
                const rules = await response.json();
                ruleSelect.innerHTML = '<option value="">כל החוקיות הפעילות</option>';
                rules.forEach(rule => {
                    const option = document.createElement('option');
                    option.value = rule.id;
                    option.textContent = rule.name;
                    ruleSelect.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading rules:', error);
            }
        });

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
                    alert('עיבוד התחיל! מספר עבודה: ' + data.job_id);
                    processForm.reset();
                    loadJobs();
                } else {
                    alert('שגיאה: ' + (data.error || 'שגיאה לא ידועה'));
                }
            } catch (error) {
                alert('שגיאה בתקשורת: ' + error.message);
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
