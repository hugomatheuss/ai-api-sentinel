{{--
Metrics and analytics dashboard
--}}
@extends('layouts.app')

@section('title', 'Metrics & Analytics')

@section('content')
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Metrics & Analytics</h1>
            <p class="mt-2 text-sm text-gray-700">
                Detailed insights and trends for API governance
            </p>
        </div>
        <div>
            <form method="GET" class="flex gap-2">
                <select name="days" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 days</option>
                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 days</option>
                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 days</option>
                </select>
            </form>
        </div>
    </div>
</div>

{{-- Key Metrics --}}
<div class="grid grid-cols-1 gap-6 sm:grid-cols-3 mb-8">
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Total Validations</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $totalValidations }}</dd>
    </div>

    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Success Rate</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight
            {{ $successRate >= 80 ? 'text-green-600' : ($successRate >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
            {{ $successRate }}%
        </dd>
    </div>

    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Breaking Changes</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
            {{ $breakingChangesTrends->sum('count') }}
        </dd>
    </div>
</div>

{{-- Charts --}}
<div class="grid grid-cols-1 gap-6 mb-8">
    {{-- Validation Trends --}}
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Validation Trends ({{ $days }} days)</h3>
            <canvas id="validationTrendsChart" height="100"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-8">
    {{-- Activity by Type --}}
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Activity by Type</h3>
            <canvas id="activityChart" height="250"></canvas>
        </div>
    </div>

    {{-- Common Issues --}}
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Top 10 Common Issues</h3>
            <canvas id="commonIssuesChart" height="250"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validation Trends Chart
    const validationTrendsCtx = document.getElementById('validationTrendsChart');
    if (validationTrendsCtx) {
        const validationData = @json($validationTrends);

        new Chart(validationTrendsCtx, {
            type: 'line',
            data: {
                labels: validationData.map(d => d.date),
                datasets: [
                    {
                        label: 'Passed',
                        data: validationData.map(d => d.passed),
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.3
                    },
                    {
                        label: 'Failed',
                        data: validationData.map(d => d.failed),
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Activity Chart
    const activityCtx = document.getElementById('activityChart');
    if (activityCtx) {
        const activityData = @json($activityTrends);
        const activityCounts = {
            validation: 0,
            api: 0,
            webhook: 0,
            contract: 0
        };

        Object.values(activityData).forEach(day => {
            day.forEach(activity => {
                if (activityCounts.hasOwnProperty(activity.log_name)) {
                    activityCounts[activity.log_name] += activity.count;
                }
            });
        });

        new Chart(activityCtx, {
            type: 'doughnut',
            data: {
                labels: ['Validation', 'API', 'Webhook', 'Contract'],
                datasets: [{
                    data: [
                        activityCounts.validation,
                        activityCounts.api,
                        activityCounts.webhook,
                        activityCounts.contract
                    ],
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(168, 85, 247)',
                        'rgb(251, 191, 36)'
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

    // Common Issues Chart
    const commonIssuesCtx = document.getElementById('commonIssuesChart');
    if (commonIssuesCtx) {
        const issues = @json($commonIssues);
        const issueLabels = Object.keys(issues).map(key => key.replace(/_/g, ' '));
        const issueValues = Object.values(issues);

        new Chart(commonIssuesCtx, {
            type: 'bar',
            data: {
                labels: issueLabels,
                datasets: [{
                    label: 'Occurrences',
                    data: issueValues,
                    backgroundColor: 'rgb(99, 102, 241)',
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
});
</script>
@endsection

