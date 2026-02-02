<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('OpenRouter Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">
                        Configure OpenRouter.ai for AI tagging rules and conversations. The API key and model are used when generating rules and chatting in AI Conversations.
                    </p>

                    <form method="POST" action="{{ route('settings.openrouter.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="openrouter_api_key" class="block text-sm font-medium text-gray-700">OpenRouter API Key</label>
                            @if($hasApiKey)
                                <p class="mt-1 text-xs text-gray-500 mb-1">Current key is set @if($apiKeyMasked)(e.g. {{ $apiKeyMasked }})@endif. Leave the field below blank to keep it.</p>
                            @else
                                <p class="mt-1 text-xs text-gray-500 mb-1">Get your key at <a href="https://openrouter.ai/keys" target="_blank" rel="noopener" class="text-blue-600 hover:underline">openrouter.ai/keys</a>.</p>
                            @endif
                            <input type="password" name="openrouter_api_key" id="openrouter_api_key" autocomplete="off"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="{{ $hasApiKey ? 'Leave blank to keep current key' : 'sk-or-v1-...' }}">
                            @error('openrouter_api_key')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="openrouter_default_model" class="block text-sm font-medium text-gray-700">Default Model</label>
                            <p class="mt-1 text-xs text-gray-500 mb-1">Model used for AI rule generation and chat.</p>
                            <select name="openrouter_default_model" id="openrouter_default_model" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($models as $value => $label)
                                    <option value="{{ $value }}" {{ (old('openrouter_default_model', $currentModel) === $value) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('openrouter_default_model')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
