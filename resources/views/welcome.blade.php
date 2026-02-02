<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Shopify Tags System') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <style>
        *,::after,::before{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}
        body{margin:0;font-family:Figtree,ui-sans-serif,system-ui,sans-serif;line-height:1.5;-webkit-text-size-adjust:100%}
        .min-h-screen{min-height:100vh}
        .flex{display:flex}
        .flex-col{flex-direction:column}
        .items-center{align-items:center}
        .justify-center{justify-content:center}
        .bg-gray-50{background-color:#f9fafb}
        .bg-white{background-color:#fff}
        .rounded-lg{border-radius:0.5rem}
        .shadow-sm{box-shadow:0 1px 2px 0 rgb(0 0 0 / 0.05)}
        .p-6{padding:1.5rem}
        .p-8{padding:2rem}
        .mb-4{margin-bottom:1rem}
        .mb-6{margin-bottom:1.5rem}
        .text-xl{font-size:1.25rem;line-height:1.75rem}
        .text-2xl{font-size:1.5rem;line-height:2rem}
        .font-semibold{font-weight:600}
        .text-gray-800{color:#1f2937}
        .text-gray-600{color:#4b5563}
        .text-sm{font-size:0.875rem;line-height:1.25rem}
        a{color:#2563eb;text-decoration:none}
        a:hover{text-decoration:underline}
        .space-x-4 > * + *{margin-left:1rem}
        .inline-flex{align-items:center;display:inline-flex}
        .px-4{padding-left:1rem;padding-right:1rem}
        .py-2{padding-top:0.5rem;padding-bottom:0.5rem}
        .rounded-md{border-radius:0.375rem}
        .bg-indigo-600{background-color:#4f46e5}
        .text-white{color:#fff}
        .hover\:bg-indigo-700:hover{background-color:#4338ca}
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center bg-gray-50">
    <div class="bg-white rounded-lg shadow-sm p-8 max-w-md w-full mx-4">
        <h1 class="text-2xl font-semibold text-gray-800 mb-2">{{ config('app.name', 'Shopify Tags System') }}</h1>
        <p class="text-gray-600 text-sm mb-6">
            Manage Shopify order tags, rules, and AI-powered tagging. Connect stores, define rules, and process orders in bulk.
        </p>

        @if (Route::has('login'))
            <nav class="flex flex-col gap-2">
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-flex px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        Log in
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Register
                        </a>
                    @endif
                @endauth
            </nav>
        @endif
    </div>
</body>
</html>
