@extends('layouts.app')

@section('title', 'Upload Nova Versão')

@section('content')
<div class="mb-4">
    <a href="{{ route('contracts.show', $contract) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
        ← Voltar para {{ $contract->title }}
    </a>
</div>

<div class="md:grid md:grid-cols-3 md:gap-6">
    <div class="md:col-span-1">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Upload Nova Versão</h3>
        <p class="mt-1 text-sm text-gray-600">
            Faça upload de um arquivo OpenAPI (YAML ou JSON) para <strong>{{ $contract->title }}</strong>.
        </p>
        <div class="mt-4 rounded-md bg-blue-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3 flex-1 md:flex md:justify-between">
                    <p class="text-sm text-blue-700">
                        <strong>Dicas:</strong><br/>
                        • Use versionamento semântico (ex: 1.0.0)<br/>
                        • Arquivos suportados: .yaml, .yml, .json<br/>
                        • Tamanho máximo: 2MB
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5 md:col-span-2 md:mt-0">
        <form action="{{ route('contract-versions.store', $contract) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="overflow-hidden shadow sm:rounded-md">
                <div class="bg-white px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-6 gap-6">
                        <div class="col-span-6 sm:col-span-3">
                            <label for="version" class="block text-sm font-medium text-gray-700">Versão (SemVer) *</label>
                            <input type="text" name="version" id="version" value="{{ old('version') }}" required pattern="^\d+\.\d+\.\d+$" placeholder="1.0.0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('version') border-red-300 @enderror" />
                            @error('version')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Formato: MAJOR.MINOR.PATCH (ex: 1.0.0)</p>
                        </div>

                        <div class="col-span-6">
                            <label for="file" class="block text-sm font-medium text-gray-700">Arquivo OpenAPI *</label>
                            <div class="mt-2 flex justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="mt-4 flex text-sm leading-6 text-gray-600">
                                        <label for="file" class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500">
                                            <span>Upload arquivo</span>
                                            <input id="file" name="file" type="file" class="sr-only" accept=".yaml,.yml,.json" required />
                                        </label>
                                        <p class="pl-1">ou arraste e solte</p>
                                    </div>
                                    <p class="text-xs leading-5 text-gray-600">YAML, YML ou JSON até 2MB</p>
                                </div>
                            </div>
                            @error('file')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-6">
                            <div class="rounded-md bg-yellow-50 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-yellow-800">Atenção</h3>
                                        <div class="mt-2 text-sm text-yellow-700">
                                            <p>O arquivo será validado automaticamente. Certifique-se de que:</p>
                                            <ul class="list-disc space-y-1 pl-5 mt-2">
                                                <li>É um arquivo OpenAPI 3.x válido</li>
                                                <li>Contém as chaves obrigatórias (openapi, info, paths)</li>
                                                <li>A versão informada é única para este contrato</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 text-right sm:px-6">
                    <a href="{{ route('contracts.show', $contract) }}" class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Cancelar
                    </a>
                    <button type="submit" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Upload e Validar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Preview do arquivo selecionado
document.getElementById('file').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    if (fileName) {
        const label = this.previousElementSibling;
        label.textContent = fileName;
        label.classList.add('text-green-600');
    }
});
</script>
@endsection

