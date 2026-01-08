<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Analysis Report - API Sentinel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                        Analysis Report
                    </h1>
                    <a
                        href="{{ route('contracts.show', $contract) }}"
                        class="rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                    >
                        Back to Contract
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="mb-6 rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="size-5 text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            {{ session('success') }}
                        </p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Report Summary --}}
            <div class="mb-6 overflow-hidden bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-medium leading-6 text-gray-900">
                                Report Summary
                            </h2>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                                Contract: {{ $contract->name }} v{{ $version->version }}
                            </p>
                        </div>
                        @if($latestReport)
                        <div class="flex items-center gap-2">
                            @if($latestReport->status === 'passed')
                            <span class="inline-flex items-center rounded-md bg-green-50 px-3 py-1 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                ✓ Passed
                            </span>
                            @else
                            <span class="inline-flex items-center rounded-md bg-red-50 px-3 py-1 text-sm font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                ✗ Failed
                            </span>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                @if($latestReport)
                <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-3">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($latestReport->status) }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Issues Found</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ count($latestReport->issues ?? []) }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Breaking Changes</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ count($latestReport->breaking_changes ?? []) }}</dd>
                        </div>
                        <div class="sm:col-span-3">
                            <dt class="text-sm font-medium text-gray-500">Processed At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $latestReport->processed_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>
                @else
                <div class="border-t border-gray-200 px-4 py-5 text-center sm:px-6">
                    <p class="text-sm text-gray-500">No analysis report available yet.</p>
                    <a
                        href="{{ route('contracts.versions.analyze', ['contract' => $contract->id, 'version' => $version->id]) }}"
                        class="mt-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        Run Analysis
                    </a>
                </div>
                @endif
            </div>

            @if($latestReport)
                {{-- Breaking Changes --}}
                @if(!empty($latestReport->breaking_changes))
                <div class="mb-6 overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h2 class="text-lg font-medium leading-6 text-gray-900">
                            Breaking Changes ({{ count($latestReport->breaking_changes) }})
                        </h2>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            Changes that may break existing API consumers.
                        </p>
                    </div>
                    <div class="border-t border-gray-200">
                        @php
                            $grouped = collect($latestReport->breaking_changes)->groupBy('category');
                            $categoryLabels = [
                                'endpoints' => 'Endpoints',
                                'parameters' => 'Parameters',
                                'responses' => 'Responses',
                                'request_body' => 'Request Body',
                                'schemas' => 'Schemas',
                                'security' => 'Security',
                                'other' => 'Other',
                            ];
                        @endphp

                        @foreach($grouped as $category => $changes)
                        <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
                            <h3 class="mb-3 text-sm font-semibold text-gray-700">
                                {{ $categoryLabels[$category] ?? ucfirst($category) }} ({{ count($changes) }})
                            </h3>
                            <ul role="list" class="space-y-3">
                                @foreach($changes as $change)
                                <li class="flex items-start gap-3">
                                    @if($change['severity'] === 'critical')
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                        CRITICAL
                                    </span>
                                    @elseif($change['severity'] === 'warning')
                                    <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                                        WARNING
                                    </span>
                                    @else
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                        INFO
                                    </span>
                                    @endif

                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $change['message'] }}</p>
                                        <div class="mt-1 flex flex-wrap gap-2 text-xs text-gray-500">
                                            @if(isset($change['path']))
                                            <span class="font-mono">{{ $change['method'] ?? '' }} {{ $change['path'] }}</span>
                                            @endif
                                            @if(isset($change['parameter']))
                                            <span>• Parameter: <code class="rounded bg-gray-100 px-1">{{ $change['parameter'] }}</code></span>
                                            @endif
                                            @if(isset($change['old_type']) && isset($change['new_type']))
                                            <span>• Type: <code class="rounded bg-gray-100 px-1">{{ $change['old_type'] }}</code> → <code class="rounded bg-gray-100 px-1">{{ $change['new_type'] }}</code></span>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Issues --}}
                @if(!empty($latestReport->issues))
                <div class="mb-6 overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h2 class="text-lg font-medium leading-6 text-gray-900">
                            Issues ({{ count($latestReport->issues) }})
                        </h2>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            Validation issues found in the contract.
                        </p>
                    </div>
                    <div class="border-t border-gray-200">
                        <ul role="list" class="divide-y divide-gray-200">
                            @foreach($latestReport->issues as $issue)
                            <li class="px-4 py-4 sm:px-6">
                                <div class="flex items-start gap-3">
                                    @if($issue['severity'] === 'error')
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                        ERROR
                                    </span>
                                    @elseif($issue['severity'] === 'warning')
                                    <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                                        WARNING
                                    </span>
                                    @endif

                                    <div class="flex-1">
                                        <p class="text-sm text-gray-900">{{ $issue['message'] }}</p>
                                        @if(isset($issue['type']))
                                        <p class="mt-1 text-xs text-gray-500">Type: {{ $issue['type'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                {{-- Success State --}}
                @if(empty($latestReport->breaking_changes) && empty($latestReport->issues))
                <div class="mb-6 rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="shrink-0">
                            <svg class="size-5 text-green-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">
                                No issues found
                            </h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p>
                                    The contract validation passed successfully. No breaking changes or issues were detected.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endif

            {{-- Endpoints List --}}
            <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h2 class="text-lg font-medium leading-6 text-gray-900">
                        Endpoints ({{ $version->endpoints->count() }})
                    </h2>
                </div>
                <div class="border-t border-gray-200">
                    <ul role="list" class="divide-y divide-gray-200">
                        @forelse($version->endpoints as $endpoint)
                        <li class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                        {{ strtoupper($endpoint->method) }}
                                    </span>
                                    <span class="font-mono text-sm text-gray-900">{{ $endpoint->path }}</span>
                                </div>
                                @if($endpoint->summary)
                                <p class="text-sm text-gray-500">{{ $endpoint->summary }}</p>
                                @endif
                            </div>
                        </li>
                        @empty
                        <li class="px-4 py-4 text-center text-sm text-gray-500 sm:px-6">
                            No endpoints found in this version.
                        </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

