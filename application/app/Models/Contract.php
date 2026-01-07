<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa um Contrato OpenAPI.
 *
 * Agrupa versões de um mesmo contrato OpenAPI, permitindo rastreamento
 * de evolução e comparação entre versões.
 */
class Contract extends Model
{
    /** @use HasFactory<\Database\Factories\ContractFactory> */
    use HasFactory;

    protected $fillable = [
        'api_id',
        'title',
        'description',
    ];

    /**
     * API à qual este contrato pertence
     */
    public function api()
    {
        return $this->belongsTo(Api::class);
    }

    /**
     * Versões deste contrato
     */
    public function versions()
    {
        return $this->hasMany(ContractVersion::class);
    }

    /**
     * Versão mais recente (por created_at)
     */
    public function latestVersion()
    {
        return $this->hasOne(ContractVersion::class)->latestOfMany();
    }
}
