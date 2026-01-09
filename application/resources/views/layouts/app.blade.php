<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'API Sentinel') }} - @yield('title', 'Governança de APIs')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body class="bg-gray-50 antialiased">
    <div class="min-h-screen">
        {{-- Header --}}
        <header class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                            <a href="{{ route('apis.index') }}" class="hover:text-indigo-600">
                                API Sentinel
                            </a>
                        </h1>
                        <p class="mt-1 text-sm text-gray-600">Governança e Catalogação de APIs</p>
                    </div>
                    <nav class="flex space-x-4">
                        <a href="{{ route('apis.index') }}" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                            APIs
                        </a>
                        <a href="{{ route('logs.index') }}" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                            Activity Logs
                        </a>
                        <a href="{{ route('metrics.index') }}" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                            Metrics
                        </a>
                        <a href="/health" class="rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                            Health
                        </a>
                    </nav>
                </div>
            </div>
        </header>
        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                <div class="rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        {{-- Main Content --}}
        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @yield('content')
        </main>
        {{-- Footer --}}
        <footer class="mt-12 border-t border-gray-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <p class="text-center text-sm text-gray-600">
                    API Sentinel &copy; {{ date('Y') }} - Trabalho de Pós-Graduação em Desenvolvimento Web
                </p>
            </div>
        </footer>
    </div>
</body>
</html>
