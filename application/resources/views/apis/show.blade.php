@extends('layouts.app')
@section('title', $api->name)
@section('content')
<div class="mb-4">
    <a href="{{ route('apis.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
        ← Voltar para lista de APIs
    </a>
</div>
<div class="overflow-hidden bg-white shadow sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-semibold leading-6 text-gray-900">{{ $api->name }}</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    @if ($api->status === 'active')
                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Ativa</span>
                    @elseif ($api->status === 'deprecated')
                        <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Deprecated</span>
                    @else
                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">Retired</span>
                    @endif
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('apis.edit', $api) }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Editar
                </a>
                <form action="{{ route('apis.destroy', $api) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja remover esta API?')">
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
            <div>
                <dt class="text-sm font-medium text-gray-500">Descrição</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $api->description ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Base URL</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    @if ($api->base_url)
                        <a href="{{ $api->base_url }}" target="_blank" class="text-indigo-600 hover:text-indigo-500">
                            {{ $api->base_url }}
                        </a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Owner</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $api->owner ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Cadastrado em</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $api->created_at->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>
</div>
{{-- Contratos --}}
<div class="mt-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h2 class="text-xl font-semibold leading-6 text-gray-900">Contratos</h2>
            <p class="mt-2 text-sm text-gray-700">
                Contratos OpenAPI associados a esta API
            </p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('apis.contracts.create', $api) }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                + Novo Contrato
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
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Título</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Versões</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Última Atualização</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Ações</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($api->contracts as $contract)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                                        <a href="{{ route('contracts.show', $contract) }}" class="hover:text-indigo-600">
                                            {{ $contract->title }}
                                        </a>
                                        @if ($contract->description)
                                            <p class="text-xs text-gray-500">{{ Str::limit($contract->description, 80) }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $contract->versions->count() }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $contract->updated_at->diffForHumans() }}
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <a href="{{ route('contracts.show', $contract) }}" class="text-indigo-600 hover:text-indigo-900">Ver detalhes</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-8 text-center text-sm text-gray-500">
                                        Nenhum contrato cadastrado ainda.
                                        <a href="{{ route('apis.contracts.create', $api) }}" class="font-medium text-indigo-600 hover:text-indigo-500">Adicione o primeiro contrato</a>
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
