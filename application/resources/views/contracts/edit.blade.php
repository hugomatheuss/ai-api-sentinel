{{--
Formulário para editar um contrato OpenAPI existente.
--}}

@extends('layouts.app')

@section('title', 'Editar Contrato')

@section('content')
<div class="mb-4">
    <a href="{{ route('contracts.show', $contract) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
        ← Voltar para detalhes do contrato
    </a>
</div>

<div class="md:grid md:grid-cols-3 md:gap-6">
    <div class="md:col-span-1">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Editar Contrato</h3>
        <p class="mt-1 text-sm text-gray-600">
            Atualize as informações do contrato <strong>{{ $contract->title }}</strong>.
        </p>
    </div>

    <div class="mt-5 md:col-span-2 md:mt-0">
        <form action="{{ route('contracts.update', $contract) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="overflow-hidden shadow sm:rounded-md">
                <div class="bg-white px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-6 gap-6">
                        <div class="col-span-6">
                            <label for="title" class="block text-sm font-medium text-gray-700">Título *</label>
                            <input type="text" name="title" id="title" value="{{ old('title', $contract->title) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('title') border-red-300 @enderror" />
                            @error('title')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-6">
                            <label for="description" class="block text-sm font-medium text-gray-700">Descrição</label>
                            <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('description') border-red-300 @enderror">{{ old('description', $contract->description) }}</textarea>
                            @error('description')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 text-right sm:px-6">
                    <a href="{{ route('contracts.show', $contract) }}" class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Cancelar
                    </a>
                    <button type="submit" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Salvar Alterações
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

