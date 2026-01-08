<?php

namespace App\Services;

use cebe\openapi\spec\OpenApi;

/**
 * Serviço de validação de contratos OpenAPI.
 *
 * Verifica conformidade com especificação OpenAPI, boas práticas,
 * e detecta problemas comuns que podem causar breaking changes.
 */
class ContractValidatorService
{
    /**
     * Validar contrato OpenAPI e retornar lista de issues
     */
    public function validate(OpenApi $openapi): array
    {
        $issues = [];

        // Validar estrutura básica
        $issues = array_merge($issues, $this->validateBasicStructure($openapi));

        // Validar info section
        $issues = array_merge($issues, $this->validateInfo($openapi));

        // Validar paths
        $issues = array_merge($issues, $this->validatePaths($openapi));

        // Validar componentes
        $issues = array_merge($issues, $this->validateComponents($openapi));

        // Validar boas práticas
        $issues = array_merge($issues, $this->validateBestPractices($openapi));

        return $issues;
    }

    /**
     * Validar estrutura básica do OpenAPI
     */
    protected function validateBasicStructure(OpenApi $openapi): array
    {
        $issues = [];

        if (! isset($openapi->openapi) && ! isset($openapi->swagger)) {
            $issues[] = [
                'severity' => 'error',
                'type' => 'missing_version',
                'message' => 'OpenAPI/Swagger version is missing',
                'path' => 'root',
            ];
        }

        return $issues;
    }

    /**
     * Validar seção info
     */
    protected function validateInfo(OpenApi $openapi): array
    {
        $issues = [];

        if (! isset($openapi->info)) {
            $issues[] = [
                'severity' => 'error',
                'type' => 'missing_info',
                'message' => 'Info section is required',
                'path' => 'info',
            ];

            return $issues;
        }

        if (! isset($openapi->info->title) || empty($openapi->info->title)) {
            $issues[] = [
                'severity' => 'error',
                'type' => 'missing_title',
                'message' => 'API title is required in info section',
                'path' => 'info.title',
            ];
        }

        if (! isset($openapi->info->version) || empty($openapi->info->version)) {
            $issues[] = [
                'severity' => 'error',
                'type' => 'missing_version',
                'message' => 'API version is required in info section',
                'path' => 'info.version',
            ];
        }

        if (! isset($openapi->info->description) || empty($openapi->info->description)) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'missing_description',
                'message' => 'API description is recommended',
                'path' => 'info.description',
            ];
        }

        return $issues;
    }

    /**
     * Validar paths (endpoints)
     */
    protected function validatePaths(OpenApi $openapi): array
    {
        $issues = [];

        if (! isset($openapi->paths) || empty((array) $openapi->paths)) {
            $issues[] = [
                'severity' => 'warning',
                'type' => 'no_paths',
                'message' => 'No paths/endpoints defined in the API',
                'path' => 'paths',
            ];

            return $issues;
        }

        foreach ($openapi->paths as $path => $pathItem) {
            $httpMethods = ['get', 'post', 'put', 'delete', 'patch', 'head', 'options', 'trace'];

            foreach ($httpMethods as $method) {
                if (! isset($pathItem->$method)) {
                    continue;
                }

                $operation = $pathItem->$method;

                // Validar operationId único
                if (! isset($operation->operationId)) {
                    $issues[] = [
                        'severity' => 'warning',
                        'type' => 'missing_operation_id',
                        'message' => 'operationId is recommended for each operation',
                        'path' => "paths.{$path}.{$method}",
                    ];
                }

                // Validar summary
                if (! isset($operation->summary) || empty($operation->summary)) {
                    $issues[] = [
                        'severity' => 'warning',
                        'type' => 'missing_summary',
                        'message' => 'Summary is recommended for better documentation',
                        'path' => "paths.{$path}.{$method}",
                    ];
                }

                // Validar responses
                if (! isset($operation->responses) || empty($operation->responses)) {
                    $issues[] = [
                        'severity' => 'error',
                        'type' => 'missing_responses',
                        'message' => 'At least one response must be defined',
                        'path' => "paths.{$path}.{$method}.responses",
                    ];
                }

                // Validar que pelo menos uma resposta de sucesso existe
                if (isset($operation->responses)) {
                    $hasSuccessResponse = false;
                    foreach ($operation->responses as $statusCode => $response) {
                        if (is_numeric($statusCode) && $statusCode >= 200 && $statusCode < 300) {
                            $hasSuccessResponse = true;
                            break;
                        }
                    }

                    if (! $hasSuccessResponse) {
                        $issues[] = [
                            'severity' => 'warning',
                            'type' => 'no_success_response',
                            'message' => 'No 2xx success response defined',
                            'path' => "paths.{$path}.{$method}.responses",
                        ];
                    }
                }

                // Validar parâmetros obrigatórios
                if (isset($operation->parameters)) {
                    foreach ($operation->parameters as $param) {
                        if (isset($param->required) && $param->required && ! isset($param->schema)) {
                            $issues[] = [
                                'severity' => 'error',
                                'type' => 'parameter_missing_schema',
                                'message' => "Required parameter '{$param->name}' is missing schema definition",
                                'path' => "paths.{$path}.{$method}.parameters",
                            ];
                        }
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Validar componentes (schemas, etc)
     */
    protected function validateComponents(OpenApi $openapi): array
    {
        $issues = [];

        if (! isset($openapi->components)) {
            return $issues;
        }

        // Validar schemas
        if (isset($openapi->components->schemas)) {
            foreach ($openapi->components->schemas as $schemaName => $schema) {
                if (! isset($schema->type) && ! isset($schema->allOf) && ! isset($schema->oneOf) && ! isset($schema->anyOf)) {
                    $issues[] = [
                        'severity' => 'warning',
                        'type' => 'schema_missing_type',
                        'message' => "Schema '{$schemaName}' should have a type or composition keyword",
                        'path' => "components.schemas.{$schemaName}",
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Validar boas práticas
     */
    protected function validateBestPractices(OpenApi $openapi): array
    {
        $issues = [];

        // Recomendar servers section
        if (! isset($openapi->servers) || empty($openapi->servers)) {
            $issues[] = [
                'severity' => 'info',
                'type' => 'no_servers',
                'message' => 'Servers section is recommended to specify API base URLs',
                'path' => 'servers',
            ];
        }

        // Verificar se há tags
        if (! isset($openapi->tags) || empty($openapi->tags)) {
            $issues[] = [
                'severity' => 'info',
                'type' => 'no_tags',
                'message' => 'Tags are recommended for better API organization',
                'path' => 'tags',
            ];
        }

        // Verificar versionamento semântico
        if (isset($openapi->info->version)) {
            $version = $openapi->info->version;
            if (! preg_match('/^\d+\.\d+\.\d+/', $version)) {
                $issues[] = [
                    'severity' => 'info',
                    'type' => 'non_semver_version',
                    'message' => 'Consider using semantic versioning (e.g., 1.0.0)',
                    'path' => 'info.version',
                ];
            }
        }

        return $issues;
    }

    /**
     * Contar issues por severidade
     */
    public function countBySeverity(array $issues): array
    {
        $counts = [
            'error' => 0,
            'warning' => 0,
            'info' => 0,
        ];

        foreach ($issues as $issue) {
            $severity = $issue['severity'] ?? 'info';
            if (isset($counts[$severity])) {
                $counts[$severity]++;
            }
        }

        return $counts;
    }

    /**
     * Determinar status geral baseado nos issues
     */
    public function determineStatus(array $issues): string
    {
        $counts = $this->countBySeverity($issues);

        if ($counts['error'] > 0) {
            return 'failed';
        }

        if ($counts['warning'] > 0) {
            return 'warning';
        }

        return 'passed';
    }
}
