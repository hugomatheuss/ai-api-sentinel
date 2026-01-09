<?php

namespace App\Services;

use App\Contracts\LLMClient;
use App\Exceptions\LLMException;
use App\Models\ContractVersion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Serviço de análise semântica usando IA.
 *
 * Analisa contratos OpenAPI para identificar inconsistências conceituais,
 * sugerir melhorias de design, e gerar insights sobre a API.
 *
 * Why this exists:
 * - Provides AI-powered semantic analysis of API contracts
 * - Identifies naming inconsistencies and design anti-patterns
 * - Generates human-readable suggestions for API improvements
 * - Fallsback to rule-based analysis when LLM is unavailable
 *
 * Callers should rely on:
 * - Always getting a result (even if LLM fails)
 * - Issues returned in consistent format
 * - LLM being optional enhancement, not requirement
 */
class AIAnalysisService
{
    public function __construct(
        protected LLMClient $llm,
        protected CacheService $cache
    ) {}

    /**
     * Analisar um contrato OpenAPI com IA
     */
    public function analyzeContract(ContractVersion $version): array
    {
        // Try to get from cache first
        $cacheKey = "ai:analysis:{$version->id}";

        return $this->cache->remember($cacheKey, 3600, function () use ($version) {
            if (! $this->llm->isAvailable()) {
                return $this->fallbackAnalysis($version);
            }

            try {
                return $this->performLLMAnalysis($version);
            } catch (LLMException $e) {
                Log::warning('LLM analysis failed, using fallback', [
                    'error' => $e->getMessage(),
                    'version_id' => $version->id,
                ]);

                return $this->fallbackAnalysis($version);
            }
        });
    }

    /**
     * Perform LLM-powered analysis.
     */
    protected function performLLMAnalysis(ContractVersion $version): array
    {
        // Get the OpenAPI contract content
        $contractContent = $this->getContractContent($version);

        if (empty($contractContent)) {
            return $this->fallbackAnalysis($version);
        }

        // Truncate if too long (to fit in context window)
        if (strlen($contractContent) > 8000) {
            $contractContent = substr($contractContent, 0, 8000)."\n... (truncated)";
        }

        $prompt = $this->buildAnalysisPrompt($contractContent, $version);

        try {
            $response = $this->llm->chat([
                ['role' => 'system', 'content' => 'You are an API design expert. Analyze OpenAPI contracts and provide concise, actionable feedback.'],
                ['role' => 'user', 'content' => $prompt],
            ], [
                'temperature' => 0.3, // Lower temperature for more consistent analysis
                'max_tokens' => 1024,
            ]);

            return $this->parseLLMResponse($response['content']);
        } catch (\Exception $e) {
            Log::error('Error parsing LLM response', ['error' => $e->getMessage()]);

            return $this->fallbackAnalysis($version);
        }
    }

    /**
     * Build the analysis prompt for the LLM.
     */
    protected function buildAnalysisPrompt(string $contractContent, ContractVersion $version): string
    {
        return <<<'PROMPT'
Analyze this OpenAPI contract and identify:
1. Naming inconsistencies (plural/singular, casing)
2. Missing or poor descriptions
3. Design anti-patterns
4. Security concerns
5. REST best practices violations

Return your analysis as a JSON array of issues with this format:
[
  {
    "type": "naming_inconsistency",
    "severity": "warning",
    "message": "Endpoint uses singular form instead of plural",
    "category": "naming"
  }
]

OpenAPI Contract:
PROMPT."\n{$contractContent}\n\nProvide only the JSON array, no additional text.";
    }

    /**
     * Parse LLM response into issues array.
     */
    protected function parseLLMResponse(string $response): array
    {
        // Try to extract JSON from response
        $json = $this->extractJSON($response);

        if ($json) {
            $issues = json_decode($json, true);
            if (is_array($issues)) {
                return $issues;
            }
        }

        // Fallback: parse as text
        return [[
            'type' => 'ai_analysis',
            'severity' => 'info',
            'message' => trim($response),
            'category' => 'ai',
        ]];
    }

    /**
     * Extract JSON from LLM response.
     */
    protected function extractJSON(string $text): ?string
    {
        // Try to find JSON array in the text
        if (preg_match('/\[[\s\S]*\]/', $text, $matches)) {
            return $matches[0];
        }

        return null;
    }

    /**
     * Get contract content from storage.
     */
    protected function getContractContent(ContractVersion $version): string
    {
        try {
            if (Storage::exists($version->file_path)) {
                return Storage::get($version->file_path);
            }
        } catch (\Exception $e) {
            Log::warning('Could not read contract file', [
                'version_id' => $version->id,
                'file_path' => $version->file_path,
                'error' => $e->getMessage(),
            ]);
        }

        return '';
    }

    /**
     * Fallback analysis when LLM is unavailable.
     */
    protected function fallbackAnalysis(ContractVersion $version): array
    {
        return $this->analyzeNaming($version);
    }

    /**
     * Gerar sugestões de melhoria para a API
     */
    public function generateSuggestions(ContractVersion $version): array
    {
        // Use same analysis as main method
        return $this->analyzeContract($version);
    }

    /**
     * Analisar nomenclatura de endpoints
     */
    public function analyzeNaming(ContractVersion $version): array
    {
        $issues = [];

        foreach ($version->endpoints as $endpoint) {
            $path = $endpoint->path;

            // Check for plural vs singular
            if (preg_match('/\/[a-z]+\/\{[a-z]+Id\}/', $path) && ! preg_match('/\/[a-z]+s\/\{[a-z]+Id\}/', $path)) {
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

        $oldEndpointCount = $oldVersion->endpoints->count();
        $newEndpointCount = $newVersion->endpoints->count();

        if ($newEndpointCount > $oldEndpointCount) {
            $diff = $newEndpointCount - $oldEndpointCount;
            $changes[] = "✨ Added {$diff} new endpoint".($diff > 1 ? 's' : '');
        } elseif ($newEndpointCount < $oldEndpointCount) {
            $diff = $oldEndpointCount - $newEndpointCount;
            $changes[] = "🗑️ Removed {$diff} endpoint".($diff > 1 ? 's' : '');
        }

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
