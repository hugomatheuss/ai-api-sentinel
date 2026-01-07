<?php

namespace App\Http\Controllers;

use App\Models\Api;
use Illuminate\Http\Request;

/**
 * Controller para gerenciamento de APIs catalogadas.
 *
 * Fornece endpoints CRUD para listar, criar, visualizar, editar e deletar APIs.
 */
class ApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $apis = Api::with('contracts')->latest()->paginate(15);

        return view('apis.index', compact('apis'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('apis.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_url' => 'nullable|url',
            'owner' => 'nullable|string|max:255',
            'status' => 'required|in:active,deprecated,retired',
        ]);

        $api = Api::create($validated);

        return redirect()->route('apis.show', $api)
            ->with('success', 'API cadastrada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Api $api)
    {
        $api->load(['contracts.versions.validationReport']);

        return view('apis.show', compact('api'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Api $api)
    {
        return view('apis.edit', compact('api'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Api $api)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_url' => 'nullable|url',
            'owner' => 'nullable|string|max:255',
            'status' => 'required|in:active,deprecated,retired',
        ]);

        $api->update($validated);

        return redirect()->route('apis.show', $api)
            ->with('success', 'API atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Api $api)
    {
        $api->delete();

        return redirect()->route('apis.index')
            ->with('success', 'API removida com sucesso!');
    }
}
