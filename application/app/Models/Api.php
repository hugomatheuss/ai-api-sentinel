<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo que representa uma API catalogada no sistema.
 *
 * Armazena metadados básicos de APIs (nome, descrição, owner) e serve
 * como ponto de agregação para contratos OpenAPI versionados.
 */
class Api extends Model
{
    /** @use HasFactory<\Database\Factories\ApiFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'base_url',
        'owner',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Contratos associados a esta API
     */
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
