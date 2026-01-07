{{--
Formulário para cadastrar uma nova API.
--}}
@extends('layouts.app')
@section('title', 'Nova API')
@section('content')
<div class="mb-4">
    <a href="{{ route('apis.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
        ← Voltar para lista
    </a>
</div>
<div class="md:grid md:grid-cols-3 md:gap-6">
    <div class="md:col-span-1">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Nova API</h3>
        <p class="mt-1 text-sm text-gray-600">
            Cadastre uma nova API no catálogo do API Sentinel.
        </p>
    </div>
    <div class="mt-5 md:col-span-2 md:mt-0">
        <form action="{{ route('apis.store') }}" method="POST">
            @csrf
            <div class="overflow-hidden shadow sm:rounded-md">
                <div class="bg-white px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-6 gap-6">
                        <div class="col-span-6">
                            <label for="name" class="block text-sm font-medium text-gray-700">Nome *</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('name') border-red-300 @enderror" />
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-span-6">
                            <label for="description" class="block text-sm font-medium text-gray-700">Descrição</label>
                            <textarea name="description" id="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('description') border-red-300 @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="base_url" class="block text-sm font-medium text-gray-700">Base URL</label>
                            <input type="url" name="base_url" id="base_url" value="{{ old('base_url') }}" placeholder="https://api.example.com" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('base_url') border-red-300 @enderror" />
                            @error('base_url')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="owner" class="block text-sm font-medium text-gray-700">Owner / Responsável</label>
                            <input type="text" name="owner" id="owner" value="{{ old('owner') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('owner') border-red-300 @enderror" />
                            @error('owner')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                            <select name="status" id="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('status') border-red-300 @enderror">
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Ativa</option>
                                <option value="deprecated" {{ old('status') === 'deprecated' ? 'selected' : '' }}>Deprecated</option>
                                <option value="retired" {{ old('status') === 'retired' ? 'selected' : '' }}>Retired</option>
                            </select>
                            @error('status')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 text-right sm:px-6">
                    <a href="{{ route('apis.index') }}" class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Cancelar
                    </a>
                    <button type="submit" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Cadastrar API
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
