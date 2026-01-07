@extends('layouts.app')

@section('title', 'Contratos - ' . $api->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('apis.show', $api) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
        ← Voltar para {{ $api->name }}
    </a>
</div>

<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold leading-6 text-gray-900">Contratos - {{ $api->name }}</h1>
        <p class="mt-2 text-sm text-gray-700">
            Contratos OpenAPI associados a esta API. Total: {{ $contracts->total() }}
        </p>
    </div>
    <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <a href="{{ route('apis.contracts.create', $api) }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            + Novo Contrato
        </a>
    </div>
</div>

<div class="mt-8 flow-root">
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
                        @forelse ($contracts as $contract)
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

            @if ($contracts->hasPages())
                <div class="mt-4">
                    {{ $contracts->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

