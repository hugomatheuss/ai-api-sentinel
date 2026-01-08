<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Services\ContractParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Controller para gerenciamento de versões de contratos OpenAPI.
 *
 * Responsável por upload, parsing, validação e armazenamento de
 * diferentes versões de especificações OpenAPI.
 */
class ContractVersionController extends Controller
{
    /**
     * Show the form for uploading a new contract version.
     */
    public function create(Contract $contract)
    {
        return view('contract-versions.create', compact('contract'));
    }

    /**
     * Store a newly uploaded contract version.
     */
    public function store(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'version' => 'required|string|regex:/^\d+\.\d+\.\d+$/',
            'file' => [
                'required',
                'file',
                'max:2048',
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    $allowedExtensions = ['yaml', 'yml', 'json'];

                    if (! in_array($extension, $allowedExtensions)) {
                        $fail('O arquivo deve ser do tipo: YAML, YML ou JSON.');
                    }
                },
            ],
        ]);

        // Verificar se versão já existe
        if ($contract->versions()->where('version', $validated['version'])->exists()) {
            return back()->withErrors(['version' => 'Esta versão já existe para este contrato.'])->withInput();
        }

        $file = $request->file('file');
        $filename = sprintf(
            '%s_v%s_%s.%s',
            \Str::slug($contract->title),
            $validated['version'],
            time(),
            $file->getClientOriginalExtension()
        );

        // Garantir que o diretório existe
        $directory = "contracts/{$contract->api_id}/{$contract->id}";
        Storage::makeDirectory($directory);

        \Log::info('Uploading contract version', [
            'directory' => $directory,
            'filename' => $filename,
            'storage_root' => storage_path('app'),
        ]);

        // Salvar arquivo em storage
        $path = Storage::putFileAs(
            $directory,
            $file,
            $filename
        );

        if (! $path) {
            \Log::error('Failed to save file to storage');

            return back()->withErrors(['file' => 'Erro ao salvar arquivo no storage.'])->withInput();
        }

        \Log::info('File saved successfully', ['path' => $path]);

        // Verificar se arquivo foi realmente salvo
        if (! Storage::exists($path)) {
            \Log::error('File does not exist after save', ['path' => $path]);

            return back()->withErrors(['file' => 'Arquivo não foi salvo corretamente.'])->withInput();
        }

        // Calcular checksum
        $checksum = hash_file('sha256', $file->getRealPath());

        // Parse e extrair metadata do OpenAPI
        $parser = new ContractParserService;

        try {
            $openapi = $parser->parse($file->getRealPath(), $file->getClientOriginalExtension());
            $metadata = $parser->extractMetadata($openapi);
            $endpoints = $parser->extractEndpoints($openapi);
        } catch (\Exception $e) {
            // Se parsing falhar, deletar arquivo e retornar erro
            Storage::delete($path);

            return back()->withErrors(['file' => 'Erro ao validar arquivo OpenAPI: '.$e->getMessage()])->withInput();
        }

        // Criar ContractVersion
        $version = $contract->versions()->create([
            'version' => $validated['version'],
            'file_path' => $path,
            'checksum' => $checksum,
            'status' => 'pending',
            'metadata' => $metadata,
        ]);

        // Salvar endpoints extraídos
        foreach ($endpoints as $endpointData) {
            $version->endpoints()->create($endpointData);
        }

        return redirect()->route('contracts.show', $contract)
            ->with('success', "Versão {$validated['version']} criada com sucesso! {$metadata['paths_count']} endpoints extraídos.");
    }

    /**
     * Display a contract version and its endpoints.
     */
    public function show(ContractVersion $contractVersion)
    {
        $contractVersion->load(['contract.api', 'endpoints', 'validationReports']);

        return view('contract-versions.show', compact('contractVersion'));
    }

    /**
     * Download a contract version file.
     */
    public function download(ContractVersion $contractVersion)
    {
        if (! Storage::exists($contractVersion->file_path)) {
            abort(404, 'Arquivo não encontrado');
        }

        return Storage::download(
            $contractVersion->file_path,
            basename($contractVersion->file_path)
        );
    }
}
