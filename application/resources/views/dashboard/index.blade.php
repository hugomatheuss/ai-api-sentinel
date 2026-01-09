@extends('layouts.app')

@section('title', 'Dashboard - API Sentinel')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">API Governance Dashboard</h1>
    <p class="mt-2 text-sm text-gray-700">
        Overview of your APIs, contracts, and governance status
    </p>
</div>

{{-- Metrics Cards --}}
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
    {{-- Total APIs --}}
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Total APIs</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $totalApis }}</dd>
        <div class="mt-2 flex items-center text-sm">
            <span class="text-green-600">{{ $apisByStatus['active'] ?? 0 }} active</span>
            @if(isset($apisByStatus['deprecated']) && $apisByStatus['deprecated'] > 0)
            <span class="ml-2 text-yellow-600">{{ $apisByStatus['deprecated'] }} deprecated</span>
            @endif
        </div>
    </div>

    {{-- Total Contracts --}}
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Contracts</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $totalContracts }}</dd>
        <div class="mt-2 text-sm text-gray-600">
            {{ $totalVersions }} versions total
        </div>
    </div>

    {{-- Validation Reports --}}
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Validations</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $totalReports }}</dd>
        <div class="mt-2 flex items-center text-sm">
            <span class="text-green-600">{{ $reportsByStatus['passed'] ?? 0 }} passed</span>
            @if(isset($reportsByStatus['failed']) && $reportsByStatus['failed'] > 0)
            <span class="ml-2 text-red-600">{{ $reportsByStatus['failed'] }} failed</span>
            @endif
        </div>
    </div>

    {{-- Health Score --}}
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Health Score</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
            @php
                $passed = $reportsByStatus['passed'] ?? 0;
                $total = $totalReports > 0 ? $totalReports : 1;
                $score = round(($passed / $total) * 100);
            @endphp
            {{ $score }}%
        </dd>
        <div class="mt-2">
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                <div class="h-2 rounded-full bg-green-600" style="width: {{ $score }}%"></div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    {{-- Recent APIs --}}
    <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium leading-6 text-gray-900">Recent APIs</h2>
                <a href="{{ route('apis.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    View all →
                </a>
            </div>
        </div>
        <div class="border-t border-gray-200">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($recentApis as $api)
                <li class="px-4 py-4 hover:bg-gray-50 sm:px-6">
                    <a href="{{ route('apis.show', $api) }}" class="block">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $api->name }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $api->contracts_count ?? 0 }} contracts</p>
                            </div>
                            <div>
                                @if($api->status === 'active')
                                <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                    Active
                                </span>
                                @else
                                <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                                    {{ ucfirst($api->status) }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </a>
                </li>
                @empty
                <li class="px-4 py-8 text-center text-sm text-gray-500 sm:px-6">
                    No APIs yet. <a href="{{ route('apis.create') }}" class="font-medium text-indigo-600 hover:text-indigo-500">Create your first API</a>
                </li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Recent Validation Reports --}}
    <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h2 class="text-lg font-medium leading-6 text-gray-900">Recent Validations</h2>
        </div>
        <div class="border-t border-gray-200">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($recentReports->take(5) as $report)
                <li class="px-4 py-4 hover:bg-gray-50 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $report->contractVersion->contract->api->name ?? 'Unknown API' }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                v{{ $report->contractVersion->version }} •
                                {{ $report->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div>
                            @if($report->status === 'passed')
                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                ✓ Passed
                            </span>
                            @elseif($report->status === 'warning')
                            <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                                ⚠ Warning
                            </span>
                            @else
                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                ✗ Failed
                            </span>
                            @endif
                        </div>
                    </div>
                </li>
                @empty
                <li class="px-4 py-8 text-center text-sm text-gray-500 sm:px-6">
                    No validation reports yet
                </li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- APIs with Issues --}}
    @if($apisWithIssues->isNotEmpty())
    <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h2 class="text-lg font-medium leading-6 text-gray-900">APIs Requiring Attention</h2>
        </div>
        <div class="border-t border-gray-200">
            <ul role="list" class="divide-y divide-gray-200">
                @foreach($apisWithIssues as $report)
                <li class="px-4 py-4 hover:bg-gray-50 sm:px-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $report->contractVersion->contract->api->name ?? 'Unknown' }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                v{{ $report->contractVersion->version }} •
                                {{ $report->error_count }} errors, {{ $report->warning_count }} warnings
                            </p>
                        </div>
                        <a
                            href="{{ route('contracts.versions.report', ['contract' => $report->contractVersion->contract_id, 'version' => $report->contractVersion->id]) }}"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            View →
                        </a>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- Breaking Changes Detected --}}
    @if($recentBreakingChanges->isNotEmpty())
    <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h2 class="text-lg font-medium leading-6 text-gray-900">Recent Breaking Changes</h2>
        </div>
        <div class="border-t border-gray-200">
            <ul role="list" class="divide-y divide-gray-200">
                @foreach($recentBreakingChanges as $report)
                <li class="px-4 py-4 hover:bg-gray-50 sm:px-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $report->contractVersion->contract->api->name ?? 'Unknown' }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">
                                v{{ $report->contractVersion->version }} •
                                {{ count($report->breaking_changes ?? []) }} breaking changes detected
                            </p>
                        </div>
                        <a
                            href="{{ route('contracts.versions.report', ['contract' => $report->contractVersion->contract_id, 'version' => $report->contractVersion->id]) }}"
                            class="text-sm font-medium text-red-600 hover:text-red-500"
                        >
                            Review →
                        </a>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
</div>

{{-- Charts Section --}}
<div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
    {{-- Validation Status Chart --}}
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Validation Status Distribution</h3>
            <canvas id="validationChart" height="250"></canvas>
        </div>
    </div>

    {{-- API Status Chart --}}
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">API Status Overview</h3>
            <canvas id="apiStatusChart" height="250"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validation Status Chart
    const validationCtx = document.getElementById('validationChart');
    if (validationCtx) {
        new Chart(validationCtx, {
            type: 'doughnut',
            data: {
                labels: ['Passed', 'Warning', 'Failed'],
                datasets: [{
                    data: [
                        {{ $reportsByStatus['passed'] ?? 0 }},
                        {{ $reportsByStatus['warning'] ?? 0 }},
                        {{ $reportsByStatus['failed'] ?? 0 }}
                    ],
                    backgroundColor: [
                        'rgb(34, 197, 94)',  // green
                        'rgb(251, 191, 36)', // yellow
                        'rgb(239, 68, 68)'   // red
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }

    // API Status Chart
    const apiCtx = document.getElementById('apiStatusChart');
    if (apiCtx) {
        new Chart(apiCtx, {
            type: 'bar',
            data: {
                labels: ['Active', 'Deprecated', 'Beta'],
                datasets: [{
                    label: 'APIs',
                    data: [
                        {{ $apisByStatus['active'] ?? 0 }},
                        {{ $apisByStatus['deprecated'] ?? 0 }},
                        {{ $apisByStatus['beta'] ?? 0 }}
                    ],
                    backgroundColor: [
                        'rgb(59, 130, 246)',  // blue
                        'rgb(251, 191, 36)',  // yellow
                        'rgb(168, 85, 247)'   // purple
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});
</script>
@endsection

