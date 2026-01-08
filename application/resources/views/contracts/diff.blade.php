@extends('layouts.app')

@section('title', 'Compare Versions - ' . $contract->title)

@section('content')
<div class="mb-4">
    <a href="{{ route('contracts.show', $contract) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
        ← Back to {{ $contract->title }}
    </a>
</div>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Version Comparison</h1>
    <p class="mt-2 text-sm text-gray-700">
        Comparing v{{ $oldVersion->version }} with v{{ $newVersion->version }}
    </p>
</div>

{{-- Summary Stats --}}
<div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-4">
    <div class="overflow-hidden rounded-lg bg-red-50 px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-red-800">Breaking Changes</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-red-900">{{ $changeCounts['critical'] ?? 0 }}</dd>
    </div>

    <div class="overflow-hidden rounded-lg bg-yellow-50 px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-yellow-800">Warnings</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-yellow-900">{{ $changeCounts['warning'] ?? 0 }}</dd>
    </div>

    <div class="overflow-hidden rounded-lg bg-green-50 px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-green-800">Added Endpoints</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-green-900">{{ count($endpointComparison['added']) }}</dd>
    </div>

    <div class="overflow-hidden rounded-lg bg-blue-50 px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-blue-800">Total Changes</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-blue-900">{{ count($breakingChanges) }}</dd>
    </div>
</div>

{{-- Breaking Changes by Category --}}
@if(!empty($groupedChanges))
<div class="mb-8 overflow-hidden bg-white shadow sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6">
        <h2 class="text-lg font-medium leading-6 text-gray-900">
            Breaking Changes by Category
        </h2>
    </div>
    <div class="border-t border-gray-200">
        @foreach($groupedChanges as $category => $changes)
        <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
            <h3 class="mb-3 text-sm font-semibold text-gray-700">
                {{ ucfirst(str_replace('_', ' ', $category)) }} ({{ count($changes) }})
            </h3>
            <ul class="space-y-2">
                @foreach($changes as $change)
                <li class="flex items-start gap-2 text-sm">
                    @if($change['severity'] === 'critical')
                    <span class="inline-flex shrink-0 items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                        CRITICAL
                    </span>
                    @elseif($change['severity'] === 'warning')
                    <span class="inline-flex shrink-0 items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                        WARNING
                    </span>
                    @endif
                    <span class="text-gray-900">{{ $change['message'] }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Endpoint Comparison --}}
<div class="mb-8 overflow-hidden bg-white shadow sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6">
        <h2 class="text-lg font-medium leading-6 text-gray-900">
            Endpoint Changes
        </h2>
    </div>

    {{-- Removed Endpoints --}}
    @if(!empty($endpointComparison['removed']))
    <div class="border-t border-gray-200 bg-red-50 px-4 py-4 sm:px-6">
        <h3 class="mb-3 text-sm font-semibold text-red-900">
            Removed Endpoints ({{ count($endpointComparison['removed']) }})
        </h3>
        <ul class="space-y-2">
            @foreach($endpointComparison['removed'] as $endpoint)
            <li class="flex items-center gap-2">
                <span class="rounded bg-red-100 px-2 py-1 font-mono text-xs text-red-800">{{ $endpoint->method }}</span>
                <span class="font-mono text-sm text-red-900">{{ $endpoint->path }}</span>
                @if($endpoint->summary)
                <span class="text-xs text-red-700">- {{ $endpoint->summary }}</span>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Added Endpoints --}}
    @if(!empty($endpointComparison['added']))
    <div class="border-t border-gray-200 bg-green-50 px-4 py-4 sm:px-6">
        <h3 class="mb-3 text-sm font-semibold text-green-900">
            Added Endpoints ({{ count($endpointComparison['added']) }})
        </h3>
        <ul class="space-y-2">
            @foreach($endpointComparison['added'] as $endpoint)
            <li class="flex items-center gap-2">
                <span class="rounded bg-green-100 px-2 py-1 font-mono text-xs text-green-800">{{ $endpoint->method }}</span>
                <span class="font-mono text-sm text-green-900">{{ $endpoint->path }}</span>
                @if($endpoint->summary)
                <span class="text-xs text-green-700">- {{ $endpoint->summary }}</span>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Modified Endpoints --}}
    @if(!empty($endpointComparison['modified']))
    <div class="border-t border-gray-200 bg-yellow-50 px-4 py-4 sm:px-6">
        <h3 class="mb-3 text-sm font-semibold text-yellow-900">
            Modified Endpoints ({{ count($endpointComparison['modified']) }})
        </h3>
        <ul class="space-y-3">
            @foreach($endpointComparison['modified'] as $modification)
            <li class="rounded-md border border-yellow-200 bg-white p-3">
                <div class="mb-2 flex items-center gap-2">
                    <span class="rounded bg-yellow-100 px-2 py-1 font-mono text-xs text-yellow-800">{{ $modification['new']->method }}</span>
                    <span class="font-mono text-sm font-semibold text-gray-900">{{ $modification['new']->path }}</span>
                </div>
                <div class="text-xs text-gray-600">
                    Parameters or responses changed
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Unchanged Endpoints --}}
    @if(!empty($endpointComparison['unchanged']))
    <div class="border-t border-gray-200 px-4 py-4 sm:px-6">
        <details>
            <summary class="cursor-pointer text-sm font-semibold text-gray-700">
                Unchanged Endpoints ({{ count($endpointComparison['unchanged']) }})
            </summary>
            <ul class="mt-3 space-y-1">
                @foreach($endpointComparison['unchanged'] as $endpoint)
                <li class="flex items-center gap-2 text-sm text-gray-600">
                    <span class="rounded bg-gray-100 px-2 py-1 font-mono text-xs">{{ $endpoint->method }}</span>
                    <span class="font-mono">{{ $endpoint->path }}</span>
                </li>
                @endforeach
            </ul>
        </details>
    </div>
    @endif
</div>

{{-- Contract Source Diff --}}
@if($oldContent && $newContent)
<div class="overflow-hidden bg-white shadow sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6">
        <h2 class="text-lg font-medium leading-6 text-gray-900">
            Source Comparison
        </h2>
        <p class="mt-1 text-sm text-gray-500">
            Side-by-side view of contract files
        </p>
    </div>
    <div class="border-t border-gray-200">
        <div class="grid grid-cols-2 divide-x divide-gray-200">
            <div class="px-4 py-4">
                <h3 class="mb-2 text-sm font-semibold text-gray-700">
                    v{{ $oldVersion->version }}
                </h3>
                <pre class="overflow-x-auto rounded bg-gray-50 p-4 text-xs"><code>{{ Str::limit($oldContent, 2000) }}</code></pre>
            </div>
            <div class="px-4 py-4">
                <h3 class="mb-2 text-sm font-semibold text-gray-700">
                    v{{ $newVersion->version }}
                </h3>
                <pre class="overflow-x-auto rounded bg-gray-50 p-4 text-xs"><code>{{ Str::limit($newContent, 2000) }}</code></pre>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

