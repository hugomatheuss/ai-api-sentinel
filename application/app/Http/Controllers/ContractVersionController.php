<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractVersion;
use cebe\openapi\Reader;
use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'file' => 'required|file|mimes:yaml,yml,json|max:2048',
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

        // Salvar arquivo em storage
        $path = $file->storeAs(
            "contracts/{$contract->api_id}/{$contract->id}",
            $filename
        );

        // Calcular checksum
        $checksum = hash_file('sha256', $file->getRealPath());

        // Parse e extrair metadata do OpenAPI
        try {
            $metadata = $this->parseOpenApiFile($file->getRealPath(), $file->getClientOriginalExtension());
        } catch (\Exception $e) {
            // Se parsing falhar, deletar arquivo e retornar erro
            Storage::delete($path);
            return back()->withErrors(['file' => 'Erro ao validar arquivo OpenAPI: ' . $e->getMessage()])->withInput();
        }

        // Criar ContractVersion
        $version = $contract->versions()->create([
            'version' => $validated['version'],
            'file_path' => $path,
            'checksum' => $checksum,
            'status' => 'pending',
            'metadata' => $metadata,
        ]);

        return redirect()->route('contracts.show', $contract)
            ->with('success', "Versão {$validated['version']} criada com sucesso!");
    }

    /**
     * Parse OpenAPI file and extract metadata.
     */
    protected function parseOpenApiFile(string $filePath, string $extension): array
    {
        try {
            if (in_array($extension, ['yaml', 'yml'])) {
                $openapi = Reader::readFromYamlFile($filePath);
            } else {
                $openapi = Reader::readFromJsonFile($filePath);
            }

            // Validar estrutura básica
            if (!$openapi->openapi && !$openapi->swagger) {
                throw new \Exception('Arquivo não contém especificação OpenAPI/Swagger válida');
            }

            // Extrair metadata relevante
            return [
                'openapi' => $openapi->openapi ?? $openapi->swagger ?? null,
                'title' => $openapi->info->title ?? null,
                'version' => $openapi->info->version ?? null,
                'description' => $openapi->info->description ?? null,
                'servers' => $this->extractServers($openapi),
                'paths_count' => count((array) $openapi->paths),
                'components_count' => isset($openapi->components->schemas) ? count((array) $openapi->components->schemas) : 0,
            ];
        } catch (IOException | TypeErrorException | UnresolvableReferenceException $e) {
            throw new \Exception('Formato de arquivo inválido: ' . $e->getMessage());
        }
    }

    /**
     * Extract server URLs from OpenAPI spec.
     */
    protected function extractServers($openapi): array
    {
        if (!isset($openapi->servers)) {
            return [];
        }

        $servers = [];
        foreach ($openapi->servers as $server) {
            if (isset($server->url)) {
                $servers[] = [
                    'url' => $server->url,
                    'description' => $server->description ?? null,
                ];
            }
        }

        return $servers;
    }

    /**
     * Download a contract version file.
     */
    public function download(ContractVersion $contractVersion)
    {
        if (!Storage::exists($contractVersion->file_path)) {
            abort(404, 'Arquivo não encontrado');
        }

        return Storage::download(
            $contractVersion->file_path,
            basename($contractVersion->file_path)
        );
    }
}
