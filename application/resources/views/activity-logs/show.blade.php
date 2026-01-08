{{--
Activity log details page
--}}
@extends('layouts.app')

@section('title', 'Activity Log Details')

@section('content')
<div class="mb-4">
    <a href="{{ route('logs.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
        ← Back to Activity Logs
    </a>
</div>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Activity Log Details</h1>
</div>

<div class="overflow-hidden bg-white shadow sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900">{{ $log->description }}</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ $log->created_at->format('F j, Y, g:i:s A') }}</p>
    </div>

    <div class="border-t border-gray-200">
        <dl>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">ID</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">#{{ $log->id }}</dd>
            </div>

            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Category</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                        @if($log->log_name === 'validation') bg-blue-50 text-blue-700 ring-blue-600/20
                        @elseif($log->log_name === 'api') bg-green-50 text-green-700 ring-green-600/20
                        @elseif($log->log_name === 'webhook') bg-purple-50 text-purple-700 ring-purple-600/20
                        @else bg-gray-50 text-gray-700 ring-gray-600/20
                        @endif">
                        {{ ucfirst($log->log_name) }}
                    </span>
                </dd>
            </div>

            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Event</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                    {{ $log->event ? str_replace('_', ' ', ucfirst($log->event)) : '—' }}
                </dd>
            </div>

            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">IP Address</dt>
                <dd class="mt-1 font-mono text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                    {{ $log->ip_address ?? '—' }}
                </dd>
            </div>

            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Timestamp</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                    {{ $log->created_at->toIso8601String() }}
                    <span class="text-gray-500">({{ $log->created_at->diffForHumans() }})</span>
                </dd>
            </div>

            @if($log->subject_type && $log->subject_id)
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Subject</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                    <code class="rounded bg-gray-100 px-2 py-1 text-xs">{{ class_basename($log->subject_type) }}</code>
                    #{{ $log->subject_id }}
                </dd>
            </div>
            @endif

            @if($log->causer_type && $log->causer_id)
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Caused By</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                    <code class="rounded bg-gray-100 px-2 py-1 text-xs">{{ class_basename($log->causer_type) }}</code>
                    #{{ $log->causer_id }}
                </dd>
            </div>
            @endif

            @if($log->properties)
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Properties</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                    <pre class="overflow-x-auto rounded bg-gray-50 p-3 text-xs">{{ json_encode($log->properties, JSON_PRETTY_PRINT) }}</pre>
                </dd>
            </div>
            @endif
        </dl>
    </div>
</div>
@endsection

