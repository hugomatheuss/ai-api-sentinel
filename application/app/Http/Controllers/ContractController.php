<?php

namespace App\Http\Controllers;

use App\Models\Api;
use App\Models\Contract;
use Illuminate\Http\Request;

/**
 * Controller para gerenciamento de Contratos OpenAPI.
 *
 * Fornece endpoints CRUD para contratos, incluindo upload de arquivos
 * OpenAPI e versionamento.
 */
class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Api $api)
    {
        $contracts = $api->contracts()->with('latestVersion')->latest()->paginate(15);

        return view('contracts.index', compact('api', 'contracts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Api $api)
    {
        return view('contracts.create', compact('api'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Api $api)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $contract = $api->contracts()->create($validated);

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Contrato criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Contract $contract)
    {
        $contract->load(['api', 'versions.validationReport']);

        return view('contracts.show', compact('contract'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contract $contract)
    {
        return view('contracts.edit', compact('contract'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $contract->update($validated);

        return redirect()->route('contracts.show', $contract)
            ->with('success', 'Contrato atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contract $contract)
    {
        $apiId = $contract->api_id;
        $contract->delete();

        return redirect()->route('apis.show', $apiId)
            ->with('success', 'Contrato removido com sucesso!');
    }
}
