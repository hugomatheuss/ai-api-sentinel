<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Analyze Contract Version - API Sentinel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                        Analyze Contract Version
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
            {{-- Contract Info --}}
            <div class="mb-6 overflow-hidden bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h2 class="text-lg font-medium leading-6 text-gray-900">
                        Contract Information
                    </h2>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        Details about the contract and version to be analyzed.
                    </p>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Contract Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $contract->name }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Version</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $version->version }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">OpenAPI Version</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $version->openapi_version ?? 'N/A' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Created At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $version->created_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        @if($previousVersion)
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Previous Version</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $previousVersion->version }}
                                <span class="text-gray-500">(Will be used for comparison)</span>
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Endpoints Summary --}}
            <div class="mb-6 overflow-hidden bg-white shadow sm:rounded-lg">
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

            {{-- Analysis Actions --}}
            <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h2 class="text-lg font-medium leading-6 text-gray-900">
                        Start Analysis
                    </h2>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">
                        This will validate the contract and detect any breaking changes.
                    </p>
                </div>
                <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                    <form
                        action="{{ route('contracts.versions.process', ['contract' => $contract->id, 'version' => $version->id]) }}"
                        method="POST"
                    >
                        @csrf

                        <div class="rounded-md bg-blue-50 p-4">
                            <div class="flex">
                                <div class="shrink-0">
                                    <svg class="size-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1 md:flex md:justify-between">
                                    <p class="text-sm text-blue-700">
                                        The analysis will check for:
                                    </p>
                                </div>
                            </div>
                            <ul class="ml-8 mt-2 list-disc space-y-1 text-sm text-blue-700">
                                <li>OpenAPI specification compliance</li>
                                <li>Breaking changes compared to previous version</li>
                                <li>Endpoint consistency and best practices</li>
                                <li>Parameter and response validation</li>
                            </ul>
                        </div>

                        <div class="mt-5 flex justify-end gap-3">
                            <a
                                href="{{ route('contracts.show', $contract) }}"
                                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                            >
                                Cancel
                            </a>
                            <button
                                type="submit"
                                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                            >
                                Start Analysis
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

