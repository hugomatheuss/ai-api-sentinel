<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa uma versão específica de um contrato OpenAPI.
 *
 * Armazena o arquivo do contrato, checksum para integridade, e metadata
 * extraído do OpenAPI (versão, servers, etc). Rastreável via SemVer.
 */
class ContractVersion extends Model
{
    /** @use HasFactory<\Database\Factories\ContractVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'version',
        'file_path',
        'checksum',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'status' => 'string',
    ];

    /**
     * Contrato ao qual esta versão pertence
     */
    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Relatório de validação associado
     */
    public function validationReport()
    {
        return $this->hasOne(ValidationReport::class);
    }
}
