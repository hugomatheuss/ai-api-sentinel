<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo que representa um endpoint de API extraído de um contrato OpenAPI.
 *
 * Armazena informações detalhadas sobre cada endpoint: path, método HTTP,
 * parâmetros, respostas e metadados de segurança.
 */
class Endpoint extends Model
{
    /** @use HasFactory<\Database\Factories\EndpointFactory> */
    use HasFactory;

    protected $fillable = [
        'contract_version_id',
        'path',
        'method',
        'summary',
        'description',
        'parameters',
        'responses',
        'request_body',
        'security',
    ];

    protected $casts = [
        'parameters' => 'array',
        'responses' => 'array',
        'request_body' => 'array',
        'security' => 'array',
    ];

    /**
     * Versão do contrato à qual este endpoint pertence.
     */
    public function contractVersion(): BelongsTo
    {
        return $this->belongsTo(ContractVersion::class);
    }
}
