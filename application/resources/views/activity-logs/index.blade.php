{{--
Activity logs listing page
--}}
@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Activity Logs</h1>
    <p class="mt-2 text-sm text-gray-700">
        Monitor system activities, validations, and API usage
    </p>
</div>

{{-- Filters --}}
<div class="mb-6 rounded-lg bg-white p-4 shadow">
    <form method="GET" action="{{ route('logs.index') }}" class="flex gap-4">
        <div class="flex-1">
            <label for="log_name" class="block text-sm font-medium text-gray-700">Category</label>
            <select name="log_name" id="log_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">All Categories</option>
                @foreach($logNames as $name)
                    <option value="{{ $name }}" {{ request('log_name') === $name ? 'selected' : '' }}>
                        {{ ucfirst($name) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex-1">
            <label for="event" class="block text-sm font-medium text-gray-700">Event</label>
            <select name="event" id="event" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">All Events</option>
                @foreach($events as $evt)
                    <option value="{{ $evt }}" {{ request('event') === $evt ? 'selected' : '' }}>
                        {{ str_replace('_', ' ', ucfirst($evt)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Filter
            </button>
            @if(request('log_name') || request('event'))
                <a href="{{ route('logs.index') }}" class="ml-2 rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-300">
                    Clear
                </a>
            @endif
        </div>
    </form>
</div>

{{-- Logs Table --}}
<div class="overflow-hidden bg-white shadow sm:rounded-lg">
    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Time</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Category</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Event</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Description</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">IP</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                    <span class="sr-only">Actions</span>
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @forelse($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-900 sm:pl-6">
                        {{ $log->created_at->format('Y-m-d H:i:s') }}
                        <br>
                        <span class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                            @if($log->log_name === 'validation') bg-blue-50 text-blue-700 ring-blue-600/20
                            @elseif($log->log_name === 'api') bg-green-50 text-green-700 ring-green-600/20
                            @elseif($log->log_name === 'webhook') bg-purple-50 text-purple-700 ring-purple-600/20
                            @else bg-gray-50 text-gray-700 ring-gray-600/20
                            @endif">
                            {{ ucfirst($log->log_name) }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        @if($log->event)
                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                {{ str_replace('_', ' ', $log->event) }}
                            </span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-900">
                        {{ Str::limit($log->description, 80) }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-gray-500">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <a href="{{ route('logs.show', $log) }}" class="text-indigo-600 hover:text-indigo-900">
                            Details
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-8 text-center text-sm text-gray-500">
                        No activity logs found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    @if($logs->hasPages())
        <div class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection

