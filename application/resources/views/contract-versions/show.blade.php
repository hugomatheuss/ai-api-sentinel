@extends('layouts.app')

@section('title', 'Versão ' . $contractVersion->version)

@section('content')
<div class="mb-4">
    <a href="{{ route('contracts.show', $contractVersion->contract) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
        ← Voltar para {{ $contractVersion->contract->title }}
    </a>
</div>

<div class="overflow-hidden bg-white shadow sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-semibold leading-6 text-gray-900">
                    {{ $contractVersion->contract->title }} - v{{ $contractVersion->version }}
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Detalhes da versão do contrato OpenAPI
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('contract-versions.download', $contractVersion) }}" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Download
                </a>
            </div>
        </div>
    </div>

    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
            <div>
                <dt class="text-sm font-medium text-gray-500">API</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <a href="{{ route('apis.show', $contractVersion->contract->api) }}" class="text-indigo-600 hover:text-indigo-500">
                        {{ $contractVersion->contract->api->name }}
                    </a>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Versão OpenAPI</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    {{ $contractVersion->metadata['openapi'] ?? '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Status</dt>
                <dd class="mt-1 text-sm">
                    @if ($contractVersion->status === 'validated')
                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Validado</span>
                    @elseif ($contractVersion->status === 'pending')
                        <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Pendente</span>
                    @else
                        <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">Falhou</span>
                    @endif
                </dd>
            </div>

            @if ($contractVersion->metadata)
                <div>
                    <dt class="text-sm font-medium text-gray-500">Título</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $contractVersion->metadata['title'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Versão da Especificação</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $contractVersion->metadata['version'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total de Endpoints</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $contractVersion->endpoints->count() }}</dd>
                </div>

                @if (isset($contractVersion->metadata['description']))
                    <div class="col-span-3">
                        <dt class="text-sm font-medium text-gray-500">Descrição</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $contractVersion->metadata['description'] }}</dd>
                    </div>
                @endif

                @if (isset($contractVersion->metadata['servers']) && count($contractVersion->metadata['servers']) > 0)
                    <div class="col-span-3">
                        <dt class="text-sm font-medium text-gray-500">Servidores</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($contractVersion->metadata['servers'] as $server)
                                    <li>
                                        <code class="text-xs bg-gray-100 px-2 py-1 rounded">{{ $server['url'] }}</code>
                                        @if (isset($server['description']))
                                            - {{ $server['description'] }}
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </dd>
                    </div>
                @endif
            @endif

            <div>
                <dt class="text-sm font-medium text-gray-500">Checksum (SHA-256)</dt>
                <dd class="mt-1 text-xs font-mono text-gray-600">{{ $contractVersion->checksum }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Criado em</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $contractVersion->created_at->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>
</div>

{{-- Endpoints --}}
<div class="mt-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h2 class="text-xl font-semibold leading-6 text-gray-900">Endpoints</h2>
            <p class="mt-2 text-sm text-gray-700">
                Lista de todos os endpoints extraídos deste contrato
            </p>
        </div>
    </div>

    <div class="mt-4 flow-root">
        @if ($contractVersion->endpoints->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center">
                <p class="text-sm text-gray-500">Nenhum endpoint encontrado nesta versão.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($contractVersion->endpoints as $endpoint)
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-sm font-semibold
                                        @if ($endpoint->method === 'GET') bg-blue-100 text-blue-800
                                        @elseif ($endpoint->method === 'POST') bg-green-100 text-green-800
                                        @elseif ($endpoint->method === 'PUT') bg-yellow-100 text-yellow-800
                                        @elseif ($endpoint->method === 'PATCH') bg-orange-100 text-orange-800
                                        @elseif ($endpoint->method === 'DELETE') bg-red-100 text-red-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $endpoint->method }}
                                    </span>
                                    <code class="text-sm font-mono text-gray-900">{{ $endpoint->path }}</code>
                                </div>
                            </div>
                            @if ($endpoint->summary)
                                <p class="mt-2 text-sm text-gray-600">{{ $endpoint->summary }}</p>
                            @endif
                        </div>

                        <div class="px-4 py-4">
                            @if ($endpoint->description)
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-900">Descrição</h4>
                                    <p class="mt-1 text-sm text-gray-600">{{ $endpoint->description }}</p>
                                </div>
                            @endif

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                {{-- Parameters --}}
                                @if ($endpoint->parameters && count($endpoint->parameters) > 0)
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Parâmetros</h4>
                                        <div class="mt-2 space-y-2">
                                            @foreach ($endpoint->parameters as $param)
                                                <div class="rounded border border-gray-200 bg-gray-50 p-2">
                                                    <div class="flex items-center justify-between">
                                                        <code class="text-sm font-mono text-gray-900">{{ $param['name'] ?? 'N/A' }}</code>
                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-xs text-gray-500">{{ $param['in'] ?? 'N/A' }}</span>
                                                            @if ($param['required'] ?? false)
                                                                <span class="inline-flex items-center rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-700">required</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if (isset($param['description']))
                                                        <p class="mt-1 text-xs text-gray-600">{{ $param['description'] }}</p>
                                                    @endif
                                                    @if (isset($param['schema']['type']))
                                                        <p class="mt-1 text-xs text-gray-500">Type: <code>{{ $param['schema']['type'] }}</code></p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Responses --}}
                                @if ($endpoint->responses && count($endpoint->responses) > 0)
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Respostas</h4>
                                        <div class="mt-2 space-y-2">
                                            @foreach ($endpoint->responses as $statusCode => $response)
                                                <div class="rounded border border-gray-200 bg-gray-50 p-2">
                                                    <div class="flex items-center justify-between">
                                                        <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold
                                                            @if (str_starts_with($statusCode, '2')) bg-green-100 text-green-800
                                                            @elseif (str_starts_with($statusCode, '4')) bg-yellow-100 text-yellow-800
                                                            @elseif (str_starts_with($statusCode, '5')) bg-red-100 text-red-800
                                                            @else bg-gray-100 text-gray-800
                                                            @endif">
                                                            {{ $statusCode }}
                                                        </span>
                                                    </div>
                                                    @if (isset($response['description']))
                                                        <p class="mt-1 text-xs text-gray-600">{{ $response['description'] }}</p>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Request Body --}}
                            @if ($endpoint->request_body)
                                <div class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-900">Request Body</h4>
                                    <div class="mt-2 rounded border border-gray-200 bg-gray-50 p-2">
                                        @if (isset($endpoint->request_body['description']))
                                            <p class="text-xs text-gray-600">{{ $endpoint->request_body['description'] }}</p>
                                        @endif
                                        @if ($endpoint->request_body['required'] ?? false)
                                            <span class="mt-1 inline-flex items-center rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-700">required</span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Security --}}
                            @if ($endpoint->security && count($endpoint->security) > 0)
                                <div class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-900">Segurança</h4>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($endpoint->security as $sec)
                                            <span class="inline-flex items-center rounded-md bg-purple-100 px-2 py-1 text-xs font-medium text-purple-700">
                                                {{ $sec['name'] ?? 'N/A' }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

