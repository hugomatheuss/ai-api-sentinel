<?php

namespace App\Services;

use App\Models\ContractVersion;

/**
 * Serviço de análise semântica usando IA.
 *
 * Analisa contratos OpenAPI para identificar inconsistências conceituais,
 * sugerir melhorias de design, e gerar insights sobre a API.
 */
class AIAnalysisService
{
    /**
     * Analisar um contrato OpenAPI com IA
     */
    public function analyzeContract(ContractVersion $version): array
    {
        $insights = [];

        // TODO: Integrar com LLM (OpenAI, Anthropic, ou local)
        // Por enquanto, retorna análise baseada em regras

        $insights[] = [
            'type' => 'naming_consistency',
            'severity' => 'info',
            'message' => 'AI analysis is not yet configured. Add your LLM API key to enable semantic analysis.',
            'category' => 'ai',
        ];

        return $insights;
    }

    /**
     * Gerar sugestões de melhoria para a API
     */
    public function generateSuggestions(ContractVersion $version): array
    {
        $suggestions = [];

        // Placeholder para análise de IA
        // TODO: Implementar chamada ao LLM

        return $suggestions;
    }

    /**
     * Analisar nomenclatura de endpoints
     */
    public function analyzeNaming(ContractVersion $version): array
    {
        $issues = [];

        foreach ($version->endpoints as $endpoint) {
            // Verificar consistência básica de nomenclatura
            $path = $endpoint->path;

            // Check for plural vs singular
            if (preg_match('/\/[a-z]+s\/\{[a-z]+Id\}/', $path)) {
                // Good: /users/{userId}
            } elseif (preg_match('/\/[a-z]+\/\{[a-z]+Id\}/', $path)) {
                $issues[] = [
                    'type' => 'naming_inconsistency',
                    'severity' => 'info',
                    'message' => "Consider using plural form for collection: {$path}",
                    'endpoint' => $path,
                    'method' => $endpoint->method,
                    'category' => 'naming',
                ];
            }

            // Check for snake_case vs kebab-case
            if (preg_match('/_/', $path)) {
                $issues[] = [
                    'type' => 'naming_style',
                    'severity' => 'info',
                    'message' => "Consider using kebab-case instead of snake_case: {$path}",
                    'endpoint' => $path,
                    'method' => $endpoint->method,
                    'category' => 'naming',
                ];
            }
        }

        return $issues;
    }

    /**
     * Gerar changelog automático com descrição semântica
     */
    public function generateChangelog(ContractVersion $oldVersion, ContractVersion $newVersion): string
    {
        $changes = [];

        // Análise básica de mudanças
        $oldEndpointCount = $oldVersion->endpoints->count();
        $newEndpointCount = $newVersion->endpoints->count();

        if ($newEndpointCount > $oldEndpointCount) {
            $diff = $newEndpointCount - $oldEndpointCount;
            $changes[] = "✨ Added {$diff} new endpoint".($diff > 1 ? 's' : '');
        } elseif ($newEndpointCount < $oldEndpointCount) {
            $diff = $oldEndpointCount - $newEndpointCount;
            $changes[] = "🗑️ Removed {$diff} endpoint".($diff > 1 ? 's' : '');
        }

        // TODO: Usar LLM para gerar descrição mais detalhada e semântica
        // Exemplo: "Refactored user authentication endpoints to support OAuth2"

        if (empty($changes)) {
            $changes[] = 'No significant changes detected';
        }

        return "## Version {$newVersion->version}\n\n".implode("\n", $changes);
    }

    /**
     * Detectar padrões de design na API
     */
    public function detectDesignPatterns(ContractVersion $version): array
    {
        $patterns = [];

        $endpoints = $version->endpoints;

        // Check for REST patterns
        $hasGet = $endpoints->where('method', 'GET')->isNotEmpty();
        $hasPost = $endpoints->where('method', 'POST')->isNotEmpty();
        $hasPut = $endpoints->where('method', 'PUT')->isNotEmpty();
        $hasDelete = $endpoints->where('method', 'DELETE')->isNotEmpty();

        if ($hasGet && $hasPost && $hasPut && $hasDelete) {
            $patterns[] = [
                'pattern' => 'RESTful CRUD',
                'description' => 'API implements complete CRUD operations',
                'quality' => 'good',
            ];
        }

        // Check for versioning in paths
        $hasVersioning = $endpoints->contains(function ($endpoint) {
            return preg_match('/\/v\d+\//', $endpoint->path);
        });

        if ($hasVersioning) {
            $patterns[] = [
                'pattern' => 'URI Versioning',
                'description' => 'API uses version numbers in URIs',
                'quality' => 'good',
            ];
        }

        // Check for pagination patterns
        $hasPagination = $endpoints->contains(function ($endpoint) {
            if (! $endpoint->parameters) {
                return false;
            }
            $params = is_array($endpoint->parameters) ? $endpoint->parameters : json_decode(json_encode($endpoint->parameters), true);
            if (! is_array($params)) {
                return false;
            }
            foreach ($params as $param) {
                $paramData = is_array($param) ? $param : (array) $param;
                if (in_array($paramData['name'] ?? '', ['page', 'limit', 'offset', 'per_page'])) {
                    return true;
                }
            }

            return false;
        });

        if ($hasPagination) {
            $patterns[] = [
                'pattern' => 'Pagination Support',
                'description' => 'API implements pagination for collection endpoints',
                'quality' => 'good',
            ];
        }

        return $patterns;
    }

    /**
     * Calcular score de qualidade da API
     */
    public function calculateQualityScore(ContractVersion $version): array
    {
        $score = 100;
        $deductions = [];

        // Penalizar falta de descrição
        if (! $version->metadata || empty($version->metadata['description'])) {
            $score -= 10;
            $deductions[] = 'Missing API description (-10)';
        }

        // Penalizar endpoints sem summary
        $endpointsWithoutSummary = $version->endpoints->filter(function ($endpoint) {
            return empty($endpoint->summary);
        })->count();

        if ($endpointsWithoutSummary > 0) {
            $penalty = min(20, $endpointsWithoutSummary * 2);
            $score -= $penalty;
            $deductions[] = "{$endpointsWithoutSummary} endpoints without summary (-{$penalty})";
        }

        // Bonificar por boas práticas
        $patterns = $this->detectDesignPatterns($version);
        $bonus = count($patterns) * 5;
        if ($bonus > 0) {
            $score += $bonus;
            $deductions[] = "Good design patterns detected (+{$bonus})";
        }

        return [
            'score' => max(0, min(100, $score)),
            'grade' => $this->getGrade($score),
            'deductions' => $deductions,
        ];
    }

    /**
     * Converter score em nota
     */
    protected function getGrade(int $score): string
    {
        if ($score >= 90) {
            return 'A';
        }
        if ($score >= 80) {
            return 'B';
        }
        if ($score >= 70) {
            return 'C';
        }
        if ($score >= 60) {
            return 'D';
        }

        return 'F';
    }
}
