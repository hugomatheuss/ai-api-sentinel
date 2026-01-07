<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa um relatório de validação de contrato OpenAPI.
 *
 * Armazena resultados detalhados da validação (erros, warnings) e estatísticas
 * para permitir consulta histórica e tomada de decisão sobre qualidade.
 */
class ValidationReport extends Model
{
    /** @use HasFactory<\Database\Factories\ValidationReportFactory> */
    use HasFactory;

    protected $fillable = [
        'contract_version_id',
        'status',
        'report_json',
        'error_count',
        'warning_count',
    ];

    protected $casts = [
        'report_json' => 'array',
        'status' => 'string',
        'error_count' => 'integer',
        'warning_count' => 'integer',
    ];

    /**
     * Versão do contrato associada a este relatório
     */
    public function contractVersion()
    {
        return $this->belongsTo(ContractVersion::class);
    }
}
