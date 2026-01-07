@extends('layouts.app')

@section('title', $contract->title)

@section('content')
<div class="mb-4">
    <a href="{{ route('apis.show', $contract->api) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
        ← Voltar para {{ $contract->api->name }}
    </a>
</div>

<div class="overflow-hidden bg-white shadow sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-semibold leading-6 text-gray-900">{{ $contract->title }}</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    API: <a href="{{ route('apis.show', $contract->api) }}" class="text-indigo-600 hover:text-indigo-500">{{ $contract->api->name }}</a>
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('contracts.edit', $contract) }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Editar
                </a>
                <form action="{{ route('contracts.destroy', $contract) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja remover este contrato?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                        Remover
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div class="col-span-2">
                <dt class="text-sm font-medium text-gray-500">Descrição</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $contract->description ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Criado em</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $contract->created_at->format('d/m/Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Última atualização</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $contract->updated_at->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>
</div>

{{-- Versões do Contrato --}}
<div class="mt-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h2 class="text-xl font-semibold leading-6 text-gray-900">Versões do Contrato</h2>
            <p class="mt-2 text-sm text-gray-700">
                Histórico de versões deste contrato OpenAPI
            </p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="#" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                + Upload Nova Versão
            </a>
        </div>
    </div>

    <div class="mt-4 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Versão</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Validação</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Data</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Ações</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($contract->versions as $version)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                        v{{ $version->version }}
                                        @if ($version->metadata && isset($version->metadata['openapi']))
                                            <p class="text-xs text-gray-500">OpenAPI {{ $version->metadata['openapi'] }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @if ($version->status === 'validated')
                                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Validado</span>
                                        @elseif ($version->status === 'pending')
                                            <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Pendente</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">Falhou</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if ($version->validationReport)
                                            @if ($version->validationReport->status === 'pass')
                                                <span class="text-green-600">✓ Passou</span>
                                            @elseif ($version->validationReport->status === 'warning')
                                                <span class="text-yellow-600">⚠ {{ $version->validationReport->warning_count }} avisos</span>
                                            @else
                                                <span class="text-red-600">✗ {{ $version->validationReport->error_count }} erros</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">Sem relatório</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $version->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-2">
                                        <a href="#" class="text-indigo-600 hover:text-indigo-900">Download</a>
                                        @if ($version->validationReport)
                                            <a href="#" class="text-indigo-600 hover:text-indigo-900">Ver relatório</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Nenhuma versão cadastrada ainda.
                                        <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">Faça o upload da primeira versão</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

