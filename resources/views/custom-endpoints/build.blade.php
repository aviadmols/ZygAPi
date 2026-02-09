<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Build Custom Endpoint – {{ $store->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Step Indicator -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="step-indicator flex items-center" data-step="1">
                            <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold">1</div>
                            <span class="ml-2 text-sm font-medium">Select Store</span>
                        </div>
                        <div class="w-16 h-0.5 bg-gray-300"></div>
                        <div class="step-indicator flex items-center" data-step="2">
                            <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">2</div>
                            <span class="ml-2 text-sm font-medium">Platforms & Prompt</span>
                        </div>
                        <div class="w-16 h-0.5 bg-gray-300"></div>
                        <div class="step-indicator flex items-center" data-step="3">
                            <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">3</div>
                            <span class="ml-2 text-sm font-medium">Input Fields</span>
                        </div>
                        <div class="w-16 h-0.5 bg-gray-300"></div>
                        <div class="step-indicator flex items-center" data-step="4">
                            <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">4</div>
                            <span class="ml-2 text-sm font-medium">Code & Test</span>
                        </div>
                        <div class="w-16 h-0.5 bg-gray-300"></div>
                        <div class="step-indicator flex items-center" data-step="5">
                            <div class="w-8 h-8 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold">5</div>
                            <span class="ml-2 text-sm font-medium">Save & URL</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Step 1: Store Selection (Already selected, show info) -->
                    <div id="step-1" class="step-content">
                        <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Step 1: Store Selected</h3>
                            <p class="text-sm text-gray-600">Store: <strong>{{ $store->name }}</strong></p>
                            <button type="button" onclick="nextStep(2)" class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">Next →</button>
                        </div>
                    </div>

                    <!-- Step 2: Platform Selection & Prompt -->
                    <div id="step-2" class="step-content hidden">
                        <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Step 2: Select Platforms & Enter Prompt</h3>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Platforms (can select multiple)</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="platforms[]" value="shopify" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm">Shopify</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="platforms[]" value="recharge" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm">Recharge</span>
                                    </label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="prompt" class="block text-sm font-medium text-gray-700">Prompt (what should this endpoint do?)</label>
                                <textarea id="prompt" name="prompt" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Describe what you want this custom endpoint to do..."></textarea>
                                <p class="mt-1 text-xs text-gray-500">Be specific about what actions you want to perform and what data you need.</p>
                            </div>
                            <div class="flex gap-4">
                                <button type="button" onclick="previousStep(1)" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">← Previous</button>
                                <button type="button" onclick="generateInputFields()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">Generate Input Fields →</button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Input Fields (Auto-generated + Editable) -->
                    <div id="step-3" class="step-content hidden">
                        <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Step 3: Input Fields</h3>
                            <p class="text-sm text-gray-500 mb-4">Review and edit the generated input fields. You can add, remove, or modify fields as needed.</p>
                            <div id="input-fields-container" class="space-y-3 mb-4">
                                <!-- Will be populated by JavaScript -->
                            </div>
                            <button type="button" onclick="addInputField()" class="text-sm text-indigo-600 hover:text-indigo-800 mb-4">+ Add Field</button>
                            <div class="flex gap-4">
                                <button type="button" onclick="previousStep(2)" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">← Previous</button>
                                <button type="button" onclick="generateCode()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">Generate Code →</button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Code Generation & Testing -->
                    <div id="step-4" class="step-content hidden">
                        <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Step 4: Code Generation & Testing</h3>
                            <div class="mb-4">
                                <label for="generated_code" class="block text-sm font-medium text-gray-700 mb-2">Generated Code</label>
                                <textarea id="generated_code" name="generated_code" rows="15" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm" readonly></textarea>
                                <button type="button" onclick="document.getElementById('generated_code').readOnly = false; this.textContent = 'Code locked'" class="mt-2 text-sm text-indigo-600 hover:text-indigo-800">Edit Code</button>
                            </div>
                            
                            <!-- Test Section -->
                            <div id="test-section" class="mt-6 p-4 bg-white rounded-lg border border-gray-200">
                                <h4 class="text-md font-semibold text-gray-800 mb-3">Test Endpoint</h4>
                                <div id="test-parameters-container" class="space-y-3 mb-4">
                                    <!-- Will be populated by JavaScript based on input fields -->
                                </div>
                                <div class="flex gap-4 mb-4">
                                    <button type="button" onclick="testEndpoint()" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">Send Test</button>
                                    <button type="button" onclick="improveCode()" id="improve-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded hidden">Improve Code</button>
                                </div>
                                
                                <!-- AI Analysis Section -->
                                <div id="ai-analysis" class="hidden mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                    <h5 class="font-semibold text-blue-900 mb-2">🤖 AI Analysis:</h5>
                                    <div id="ai-analysis-content" class="text-sm text-gray-700 mb-3"></div>
                                    <div id="ai-issues" class="hidden mb-2">
                                        <strong class="text-red-700">Issues found:</strong>
                                        <ul id="ai-issues-list" class="list-disc list-inside text-sm text-red-600"></ul>
                                    </div>
                                    <div id="ai-suggestions" class="hidden mb-2">
                                        <strong class="text-green-700">Suggestions:</strong>
                                        <ul id="ai-suggestions-list" class="list-disc list-inside text-sm text-green-600"></ul>
                                    </div>
                                </div>
                                
                                <!-- Test Results -->
                                <div id="test-results" class="hidden mt-4 p-4 bg-gray-50 rounded-lg">
                                    <h5 class="font-semibold mb-2">Test Results:</h5>
                                    <pre id="test-results-content" class="text-sm overflow-auto max-h-64"></pre>
                                </div>
                                
                                <!-- Execution Logs -->
                                <div id="execution-logs" class="hidden mt-4 p-4 bg-gray-50 rounded-lg">
                                    <h5 class="font-semibold mb-2">Execution Logs:</h5>
                                    <pre id="execution-logs-content" class="text-sm overflow-auto max-h-64"></pre>
                                </div>
                            </div>
                            
                            <div class="flex gap-4 mt-6">
                                <button type="button" onclick="previousStep(3)" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">← Previous</button>
                                <button type="button" onclick="nextStep(5)" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">Next →</button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Save & Configure URL -->
                    <div id="step-5" class="step-content hidden">
                        <div class="mb-6 p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Step 5: Save & Configure URL</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="ep_name" class="block text-sm font-medium text-gray-700">Endpoint Name</label>
                                    <input type="text" id="ep_name" name="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                                <div>
                                    <label for="ep_slug" class="block text-sm font-medium text-gray-700">Endpoint URL Slug</label>
                                    <input type="text" id="ep_slug" name="slug" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="my-endpoint" required>
                                    <p class="mt-1 text-xs text-gray-500">This will be used in the endpoint URL</p>
                                </div>
                            </div>
                            <div class="mb-4 p-3 bg-indigo-50 rounded-lg">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Endpoint URL:</label>
                                <code id="endpoint-url-preview" class="text-sm text-indigo-700"></code>
                            </div>
                            <div class="mb-4">
                                <label for="webhook_token" class="block text-sm font-medium text-gray-700">Webhook token (optional)</label>
                                <input type="text" id="webhook_token" name="webhook_token" maxlength="64" class="mt-1 block w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm">
                            </div>
                            <form id="save-form" method="POST" action="{{ route('custom-endpoints.store') }}">
                                @csrf
                                <input type="hidden" name="store_id" value="{{ $store->id }}">
                                <input type="hidden" name="platform" id="save_platform">
                                <input type="hidden" name="prompt" id="save_prompt">
                                <input type="hidden" name="input_params" id="save_input_params">
                                <input type="hidden" name="test_return_values" id="save_test_return_values">
                                <input type="hidden" name="php_code" id="save_php_code">
                                <input type="hidden" name="name" id="save_name">
                                <input type="hidden" name="slug" id="save_slug">
                                <input type="hidden" name="webhook_token" id="save_webhook_token">
                                <div class="flex gap-4">
                                    <button type="button" onclick="previousStep(4)" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">← Previous</button>
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Save Endpoint</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        let inputFields = [];
        let generatedCode = '';
        let testResults = null;
        const storeId = {{ $store->id }};
        const csrf = document.querySelector('meta[name="csrf-token"]').content;

        function updateStepIndicator(step) {
            document.querySelectorAll('.step-indicator').forEach((el, idx) => {
                const stepNum = idx + 1;
                const circle = el.querySelector('div');
                if (stepNum <= step) {
                    circle.classList.remove('bg-gray-300', 'text-gray-600');
                    circle.classList.add('bg-indigo-600', 'text-white');
                } else {
                    circle.classList.remove('bg-indigo-600', 'text-white');
                    circle.classList.add('bg-gray-300', 'text-gray-600');
                }
            });
        }

        function showStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
            document.getElementById(`step-${step}`).classList.remove('hidden');
            currentStep = step;
            updateStepIndicator(step);
        }

        function nextStep(step) {
            showStep(step);
        }

        function previousStep(step) {
            showStep(step);
        }

        async function generateInputFields() {
            const platforms = Array.from(document.querySelectorAll('input[name="platforms[]"]:checked')).map(cb => cb.value);
            const prompt = document.getElementById('prompt').value.trim();

            if (!prompt) {
                alert('Please enter a prompt.');
                return;
            }

            if (platforms.length === 0) {
                alert('Please select at least one platform.');
                return;
            }

            try {
                const response = await fetch('/api/internal/custom-endpoints/generate-input-fields', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({
                        store_id: storeId,
                        platforms: platforms,
                        prompt: prompt
                    })
                });

                const data = await response.json();
                if (data.success && data.fields) {
                    inputFields = data.fields;
                    renderInputFields();
                    nextStep(3);
                } else {
                    alert('Error: ' + (data.error || 'Failed to generate input fields'));
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }

        function renderInputFields() {
            const container = document.getElementById('input-fields-container');
            container.innerHTML = '';
            inputFields.forEach((field, index) => {
                const div = document.createElement('div');
                div.className = 'p-3 bg-white rounded border border-gray-200';
                div.innerHTML = `
                    <div class="grid grid-cols-2 gap-4 mb-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Field Name</label>
                            <input type="text" class="input-field-name mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" value="${field.name || ''}" data-index="${index}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Type</label>
                            <select class="input-field-type mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" data-index="${index}">
                                <option value="string" ${field.type === 'string' ? 'selected' : ''}>String</option>
                                <option value="number" ${field.type === 'number' ? 'selected' : ''}>Number</option>
                                <option value="boolean" ${field.type === 'boolean' ? 'selected' : ''}>Boolean</option>
                                <option value="array" ${field.type === 'array' ? 'selected' : ''}>Array</option>
                                <option value="object" ${field.type === 'object' ? 'selected' : ''}>Object</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-2">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" class="input-field-required rounded border-gray-300" ${field.required ? 'checked' : ''} data-index="${index}">
                                <span class="ml-2 text-xs">Required</span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700">Default Value</label>
                            <input type="text" class="input-field-default mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" value="${field.default || ''}" data-index="${index}">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Description</label>
                        <textarea class="input-field-description mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" rows="2" data-index="${index}">${field.description || ''}</textarea>
                    </div>
                    <button type="button" onclick="removeInputField(${index})" class="mt-2 text-xs text-red-600 hover:text-red-800">Remove</button>
                `;
                container.appendChild(div);
            });
        }

        function addInputField() {
            inputFields.push({
                name: '',
                type: 'string',
                required: true,
                default: '',
                description: ''
            });
            renderInputFields();
        }

        function removeInputField(index) {
            inputFields.splice(index, 1);
            renderInputFields();
        }

        async function generateCode() {
            const platforms = Array.from(document.querySelectorAll('input[name="platforms[]"]:checked')).map(cb => cb.value);
            const prompt = document.getElementById('prompt').value.trim();

            // Update inputFields from form
            updateInputFieldsFromForm();

            try {
                const response = await fetch('/api/internal/custom-endpoints/generate-code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({
                        store_id: storeId,
                        platforms: platforms,
                        prompt: prompt,
                        input_schema: inputFields
                    })
                });

                const data = await response.json();
                if (data.success && data.code) {
                    generatedCode = data.code;
                    document.getElementById('generated_code').value = generatedCode;
                    renderTestParameters();
                    nextStep(4);
                } else {
                    alert('Error: ' + (data.error || 'Failed to generate code'));
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }

        function updateInputFieldsFromForm() {
            inputFields = inputFields.map((field, index) => {
                const nameEl = document.querySelector(`.input-field-name[data-index="${index}"]`);
                const typeEl = document.querySelector(`.input-field-type[data-index="${index}"]`);
                const requiredEl = document.querySelector(`.input-field-required[data-index="${index}"]`);
                const defaultEl = document.querySelector(`.input-field-default[data-index="${index}"]`);
                const descEl = document.querySelector(`.input-field-description[data-index="${index}"]`);
                
                return {
                    name: nameEl ? nameEl.value : field.name,
                    type: typeEl ? typeEl.value : field.type,
                    required: requiredEl ? requiredEl.checked : field.required,
                    default: defaultEl ? defaultEl.value : field.default,
                    description: descEl ? descEl.value : field.description
                };
            });
        }

        function renderTestParameters() {
            const container = document.getElementById('test-parameters-container');
            container.innerHTML = '';
            inputFields.forEach(field => {
                if (!field.name) return;
                const div = document.createElement('div');
                div.className = 'mb-3';
                let inputHtml = '';
                if (field.type === 'boolean') {
                    inputHtml = `<label class="flex items-center"><input type="checkbox" class="test-param rounded border-gray-300" data-name="${field.name}"><span class="ml-2 text-sm">${field.name}</span></label>`;
                } else if (field.type === 'array' || field.type === 'object') {
                    inputHtml = `<label class="block text-sm font-medium text-gray-700 mb-1">${field.name} (JSON)</label><textarea class="test-param block w-full rounded-md border-gray-300 shadow-sm text-sm" data-name="${field.name}" rows="3" placeholder='${field.type === 'array' ? '["value1", "value2"]' : '{"key": "value"}'}'></textarea>`;
                } else {
                    inputHtml = `<label class="block text-sm font-medium text-gray-700 mb-1">${field.name}${field.required ? ' *' : ''}</label><input type="${field.type === 'number' ? 'number' : 'text'}" class="test-param block w-full rounded-md border-gray-300 shadow-sm text-sm" data-name="${field.name}" ${field.required ? 'required' : ''}>`;
                }
                div.innerHTML = inputHtml;
                container.appendChild(div);
            });
        }

        async function testEndpoint() {
            updateInputFieldsFromForm();
            const testParams = {};
            document.querySelectorAll('.test-param').forEach(el => {
                const name = el.getAttribute('data-name');
                if (el.type === 'checkbox') {
                    testParams[name] = el.checked;
                } else if (el.type === 'textarea') {
                    try {
                        testParams[name] = JSON.parse(el.value);
                    } catch {
                        testParams[name] = el.value;
                    }
                } else {
                    testParams[name] = el.value;
                }
            });

            try {
                const response = await fetch('/api/internal/custom-endpoints/test', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({
                        store_id: storeId,
                        code: generatedCode,
                        input: testParams
                    })
                });

                const data = await response.json();
                testResults = data;
                
                // Show test results
                const resultsDiv = document.getElementById('test-results');
                const resultsContent = document.getElementById('test-results-content');
                resultsDiv.classList.remove('hidden');
                resultsContent.textContent = JSON.stringify(data, null, 2);
                
                // Show execution logs
                if (data.logs && data.logs.length > 0) {
                    const logsDiv = document.getElementById('execution-logs');
                    const logsContent = document.getElementById('execution-logs-content');
                    logsDiv.classList.remove('hidden');
                    logsContent.textContent = JSON.stringify(data.logs, null, 2);
                }
                
                // Show AI analysis
                if (data.analysis) {
                    const analysisDiv = document.getElementById('ai-analysis');
                    const analysisContent = document.getElementById('ai-analysis-content');
                    analysisDiv.classList.remove('hidden');
                    
                    analysisContent.innerHTML = `<p class="mb-2">${data.analysis.analysis || 'Analysis completed'}</p>`;
                    
                    // Show issues if any
                    if (data.analysis.issues && data.analysis.issues.length > 0) {
                        const issuesDiv = document.getElementById('ai-issues');
                        const issuesList = document.getElementById('ai-issues-list');
                        issuesDiv.classList.remove('hidden');
                        issuesList.innerHTML = data.analysis.issues.map(issue => `<li>${issue}</li>`).join('');
                    }
                    
                    // Show suggestions if any
                    if (data.analysis.suggestions && data.analysis.suggestions.length > 0) {
                        const suggestionsDiv = document.getElementById('ai-suggestions');
                        const suggestionsList = document.getElementById('ai-suggestions-list');
                        suggestionsDiv.classList.remove('hidden');
                        suggestionsList.innerHTML = data.analysis.suggestions.map(suggestion => `<li>${suggestion}</li>`).join('');
                    }
                    
                    // Show improve button if needed
                    if (data.analysis.needs_fix || !data.success || (data.analysis.issues && data.analysis.issues.length > 0)) {
                        document.getElementById('improve-btn').classList.remove('hidden');
                    } else {
                        document.getElementById('improve-btn').classList.add('hidden');
                    }
                } else {
                    // Fallback: show improve button if test failed
                    if (!data.success) {
                        document.getElementById('improve-btn').classList.remove('hidden');
                    } else {
                        document.getElementById('improve-btn').classList.add('hidden');
                    }
                }
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }

        async function improveCode() {
            if (!testResults) {
                alert('Please test the endpoint first.');
                return;
            }

            const improveBtn = document.getElementById('improve-btn');
            const originalText = improveBtn.textContent;
            
            // Show loading state
            improveBtn.disabled = true;
            improveBtn.textContent = '🔄 Improving...';
            improveBtn.classList.add('opacity-50', 'cursor-not-allowed');

            try {
                const response = await fetch('/api/internal/custom-endpoints/improve-code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({
                        store_id: storeId,
                        current_code: generatedCode,
                        logs: testResults.logs || [],
                        test_results: testResults
                    })
                });

                const data = await response.json();
                if (data.success && data.code) {
                    // Update the code
                    generatedCode = data.code;
                    document.getElementById('generated_code').value = generatedCode;
                    
                    // Reset test results and analysis
                    testResults = null;
                    
                    // Hide AI analysis section
                    document.getElementById('ai-analysis').classList.add('hidden');
                    document.getElementById('test-results').classList.add('hidden');
                    document.getElementById('execution-logs').classList.add('hidden');
                    document.getElementById('improve-btn').classList.add('hidden');
                    
                    // Clear test parameters
                    document.querySelectorAll('.test-param').forEach(el => {
                        if (el.type === 'checkbox') {
                            el.checked = false;
                        } else {
                            el.value = '';
                        }
                    });
                    
                    // Show success message
                    alert('✅ Code improved successfully! Please test again.');
                } else {
                    alert('Error: ' + (data.error || 'Failed to improve code'));
                }
            } catch (e) {
                alert('Error: ' + e.message);
            } finally {
                // Restore button state
                improveBtn.disabled = false;
                improveBtn.textContent = originalText;
                improveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        // Update endpoint URL preview
        document.getElementById('ep_name').addEventListener('input', function() {
            const slug = document.getElementById('ep_slug').value || this.value.toLowerCase().replace(/\s+/g, '-');
            document.getElementById('ep_slug').value = slug;
            updateEndpointUrlPreview();
        });

        document.getElementById('ep_slug').addEventListener('input', updateEndpointUrlPreview);

        function updateEndpointUrlPreview() {
            const slug = document.getElementById('ep_slug').value || 'your-endpoint';
            const url = `{{ config('app.url') }}/webhooks/custom-endpoint/${slug}`;
            document.getElementById('endpoint-url-preview').textContent = url;
        }

        // Save form handler
        document.getElementById('save-form').addEventListener('submit', function(e) {
            const platforms = Array.from(document.querySelectorAll('input[name="platforms[]"]:checked')).map(cb => cb.value);
            const prompt = document.getElementById('prompt').value;
            
            updateInputFieldsFromForm();
            
            document.getElementById('save_platform').value = platforms[0] || 'shopify';
            document.getElementById('save_prompt').value = prompt;
            document.getElementById('save_input_params').value = JSON.stringify(inputFields.map(f => ({ name: f.name })));
            document.getElementById('save_test_return_values').value = JSON.stringify([]);
            document.getElementById('save_php_code').value = generatedCode;
            document.getElementById('save_name').value = document.getElementById('ep_name').value;
            document.getElementById('save_slug').value = document.getElementById('ep_slug').value;
            document.getElementById('save_webhook_token').value = document.getElementById('webhook_token').value;
        });

        // Initialize
        showStep(1);
        updateEndpointUrlPreview();
    </script>
</x-app-layout>
