<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('שיחה עם AI') }}
            </h2>
            <a href="{{ route('ai-conversations.index') }}" class="text-gray-600 hover:text-gray-900">חזרה לרשימה</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">חנות: <strong>{{ $aiConversation->store->name }}</strong></p>
                    </div>

                    <!-- Chat Messages -->
                    <div id="chat-messages" class="mb-4 h-96 overflow-y-auto border border-gray-200 rounded-lg p-4 bg-gray-50">
                        @foreach($aiConversation->messages ?? [] as $message)
                            <div class="mb-4 {{ $message['role'] === 'user' ? 'text-right' : 'text-left' }}">
                                <div class="inline-block max-w-3xl p-3 rounded-lg {{ $message['role'] === 'user' ? 'bg-blue-500 text-white' : 'bg-white border border-gray-300' }}">
                                    <p class="text-sm">{{ $message['content'] }}</p>
                                    @if(isset($message['timestamp']))
                                        <p class="text-xs mt-1 opacity-75">{{ $message['timestamp'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Chat Input -->
                    <form id="chat-form" class="mb-4">
                        @csrf
                        <div class="flex">
                            <textarea id="message-input" name="message" rows="3" required
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="הכנס את החוקיות שלך כאן..."></textarea>
                            <button type="submit" class="mr-2 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                שלח
                            </button>
                        </div>
                    </form>

                    <!-- Order Data Input -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">דוגמת הזמנה (JSON) - אופציונלי</label>
                        <textarea id="order-data" rows="5"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                            placeholder='{"id": "123", "line_items": [...]}'></textarea>
                    </div>

                    <!-- Generate Rule Button -->
                    @if(count($aiConversation->messages ?? []) > 2)
                        <form id="generate-rule-form" class="mb-4">
                            @csrf
                            <input type="hidden" id="user-requirements" name="user_requirements">
                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                צור חוקיות מהשיחה
                            </button>
                        </form>
                    @endif

                    @if($aiConversation->generatedRule)
                        <div class="mt-4 p-4 bg-green-100 border border-green-400 rounded-lg">
                            <p class="text-sm text-green-800">חוקיות נוצרה בהצלחה!</p>
                            <a href="{{ route('tagging-rules.edit', $aiConversation->generatedRule) }}" class="text-blue-600 hover:text-blue-800">ערוך חוקיות</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        const chatForm = document.getElementById('chat-form');
        const chatMessages = document.getElementById('chat-messages');
        const messageInput = document.getElementById('message-input');
        const generateRuleForm = document.getElementById('generate-rule-form');
        const orderDataInput = document.getElementById('order-data');

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            // Add user message to UI
            addMessage('user', message);
            messageInput.value = '';

            try {
                const response = await fetch(`{{ route('ai-conversations.chat', $aiConversation) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        message: message,
                        order_data: orderDataInput.value || null
                    })
                });

                const data = await response.json();
                if (data.success) {
                    addMessage('assistant', data.message);
                } else {
                    alert('שגיאה: ' + data.error);
                }
            } catch (error) {
                alert('שגיאה בתקשורת: ' + error.message);
            }
        });

        if (generateRuleForm) {
            generateRuleForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const orderData = orderDataInput.value.trim();
                if (!orderData) {
                    alert('אנא הכנס דוגמת הזמנה');
                    return;
                }

                // Collect user requirements from messages
                const userMessages = Array.from(chatMessages.querySelectorAll('[data-role="user"]'))
                    .map(el => el.textContent.trim())
                    .join('\n');

                document.getElementById('user-requirements').value = userMessages;

                try {
                    const response = await fetch(`{{ route('ai-conversations.generate-rule', $aiConversation) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            order_data: orderData,
                            user_requirements: userMessages
                        })
                    });

                    const data = await response.json();
                    if (data.success) {
                        alert('חוקיות נוצרה בהצלחה!');
                        window.location.reload();
                    } else {
                        alert('שגיאה: ' + data.error);
                    }
                } catch (error) {
                    alert('שגיאה בתקשורת: ' + error.message);
                }
            });
        }

        function addMessage(role, content) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `mb-4 ${role === 'user' ? 'text-right' : 'text-left'}`;
            messageDiv.setAttribute('data-role', role);
            messageDiv.innerHTML = `
                <div class="inline-block max-w-3xl p-3 rounded-lg ${role === 'user' ? 'bg-blue-500 text-white' : 'bg-white border border-gray-300'}">
                    <p class="text-sm">${content}</p>
                    <p class="text-xs mt-1 opacity-75">${new Date().toLocaleString('he-IL')}</p>
                </div>
            `;
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</x-app-layout>
