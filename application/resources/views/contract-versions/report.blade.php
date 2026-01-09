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
                {{-- AI Analysis Insights --}}
                @php
                    $aiInsights = collect($latestReport->issues ?? [])->where('category', 'ai')->values();
                    $hasAiAnalysis = $aiInsights->isNotEmpty();
                @endphp

                @if($hasAiAnalysis)
                <div class="mb-6 overflow-hidden bg-gradient-to-r from-purple-50 to-indigo-50 shadow sm:rounded-lg">
                    <div class="border-b border-purple-100 bg-white/50 px-4 py-5 backdrop-blur-sm sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-purple-600">
                                <svg class="size-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold leading-6 text-gray-900">
                                    🤖 AI-Powered Insights
                                </h2>
                                <p class="mt-1 text-sm text-gray-600">
                                    Semantic analysis powered by {{ config('services.groq.api_key') ? 'Groq Llama 3.3' : 'AI' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="border-t border-purple-100 px-4 py-5 sm:px-6">
                        <div class="space-y-4">
                            @foreach($aiInsights as $insight)
                            <div class="rounded-lg border border-purple-200 bg-white p-4 shadow-sm">
                                <div class="flex items-start gap-3">
                                    @if($insight['severity'] === 'error')
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-600/20">
                                        ⚠️ CRITICAL
                                    </span>
                                    @elseif($insight['severity'] === 'warning')
                                    <span class="inline-flex items-center rounded-md bg-yellow-50 px-2.5 py-1 text-xs font-semibold text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                                        ⚡ WARNING
                                    </span>
                                    @else
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                        💡 INSIGHT
                                    </span>
                                    @endif

                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $insight['message'] }}</p>
                                        @if(isset($insight['type']))
                                        <p class="mt-1 text-xs text-gray-500">
                                            Type: <code class="rounded bg-purple-50 px-1.5 py-0.5 font-mono text-purple-700">{{ $insight['type'] }}</code>
                                        </p>
                                        @endif
                                        @if(isset($insight['endpoint']))
                                        <p class="mt-1 text-xs text-gray-500">
                                            Endpoint: <code class="rounded bg-gray-100 px-1.5 py-0.5 font-mono">{{ $insight['method'] ?? '' }} {{ $insight['endpoint'] }}</code>
                                        </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="mt-4 rounded-md bg-purple-50 p-3">
                            <p class="text-xs text-purple-700">
                                <strong>Note:</strong> AI analysis provides suggestions to improve your API design. These are recommendations and should be reviewed by your team.
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- AI Quality Score --}}
                @php
                    // Check if we have quality score in metadata or calculate it
                    $qualityScore = null;
                    if(isset($version->metadata['quality_score'])) {
                        $qualityScore = $version->metadata['quality_score'];
                    }
                @endphp

                @if($qualityScore)
                <div class="mb-6 overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-semibold leading-6 text-gray-900">API Quality Score</h3>
                                <p class="mt-1 max-w-2xl text-sm text-gray-500">AI-powered quality assessment</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @php
                                    $score = $qualityScore['score'] ?? 0;
                                    $grade = $qualityScore['grade'] ?? 'N/A';
                                    $gradeColors = [
                                        'A' => 'bg-green-100 text-green-800 ring-green-600/20',
                                        'B' => 'bg-blue-100 text-blue-800 ring-blue-600/20',
                                        'C' => 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
                                        'D' => 'bg-orange-100 text-orange-800 ring-orange-600/20',
                                        'F' => 'bg-red-100 text-red-800 ring-red-600/20',
                                    ];
                                    $colorClass = $gradeColors[$grade] ?? 'bg-gray-100 text-gray-800 ring-gray-600/20';
                                @endphp
                                <div class="text-right">
                                    <div class="text-3xl font-bold text-gray-900">{{ $score }}</div>
                                    <div class="text-xs text-gray-500">out of 100</div>
                                </div>
                                <span class="inline-flex items-center rounded-full {{ $colorClass }} px-4 py-2 text-2xl font-bold ring-1 ring-inset">
                                    {{ $grade }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @if(!empty($qualityScore['deductions']))
                    <div class="border-t border-gray-200 px-4 py-3 sm:px-6">
                        <h4 class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">Score Breakdown</h4>
                        <ul class="space-y-1">
                            @foreach($qualityScore['deductions'] as $deduction)
                            <li class="flex items-center gap-1.5 text-xs">
                                @if(str_contains($deduction, '+'))
                                <span class="inline-flex size-3.5 shrink-0 items-center justify-center rounded-sm bg-green-100">
                                    <svg class="size-2.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                </span>
                                <span class="text-green-700">{{ $deduction }}</span>
                                @else
                                <span class="inline-flex size-3.5 shrink-0 items-center justify-center rounded-sm bg-gray-100">
                                    <svg class="size-2.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                                    </svg>
                                </span>
                                <span class="text-gray-600">{{ $deduction }}</span>
                                @endif
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                @endif

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

