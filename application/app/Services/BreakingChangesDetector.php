<?php

namespace App\Services;

use App\Models\ContractVersion;
use Illuminate\Support\Facades\Storage;

/**
 * Serviço para comparação de versões de contratos e detecção de breaking changes.
 *
 * Analisa diferenças estruturais entre versões de APIs OpenAPI e identifica
 * mudanças que podem quebrar compatibilidade com consumidores existentes.
 */
class BreakingChangesDetector
{
    public function __construct(
        protected ContractParserService $parser
    ) {}

    /**
     * Detectar breaking changes entre duas versões
     */
    public function detect(ContractVersion $oldVersion, ContractVersion $newVersion): array
    {
        $breakingChanges = [];

        try {
            // Parse both versions
            $oldOpenApi = $this->parseVersion($oldVersion);
            $newOpenApi = $this->parseVersion($newVersion);

            // Compare endpoints
            $breakingChanges = array_merge(
                $breakingChanges,
                $this->compareEndpoints($oldOpenApi, $newOpenApi)
            );

            // Compare schemas
            $breakingChanges = array_merge(
                $breakingChanges,
                $this->compareSchemas($oldOpenApi, $newOpenApi)
            );

            // Compare authentication
            $breakingChanges = array_merge(
                $breakingChanges,
                $this->compareAuthentication($oldOpenApi, $newOpenApi)
            );

        } catch (\Exception $e) {
            \Log::error('Error detecting breaking changes', [
                'old_version' => $oldVersion->id,
                'new_version' => $newVersion->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $breakingChanges;
    }

    /**
     * Parse uma versão de contrato
     */
    protected function parseVersion(ContractVersion $version)
    {
        if (! Storage::exists($version->file_path)) {
            throw new \Exception("Contract file not found: {$version->file_path}");
        }

        $filePath = Storage::path($version->file_path);
        $extension = pathinfo($version->file_path, PATHINFO_EXTENSION);

        return $this->parser->parse($filePath, $extension);
    }

    /**
     * Comparar endpoints entre versões
     */
    protected function compareEndpoints($oldOpenApi, $newOpenApi): array
    {
        $changes = [];

        if (! isset($oldOpenApi->paths) || ! isset($newOpenApi->paths)) {
            return $changes;
        }

        $oldPaths = (array) $oldOpenApi->paths;
        $newPaths = (array) $newOpenApi->paths;

        // Check for removed endpoints
        foreach ($oldPaths as $path => $pathItem) {
            if (! isset($newPaths[$path])) {
                $changes[] = [
                    'type' => 'endpoint_removed',
                    'severity' => 'critical',
                    'message' => "Endpoint removed: {$path}",
                    'path' => $path,
                    'category' => 'endpoints',
                ];

                continue;
            }

            // Check for removed HTTP methods
            $httpMethods = ['get', 'post', 'put', 'delete', 'patch', 'head', 'options'];
            foreach ($httpMethods as $method) {
                if (isset($pathItem->$method) && ! isset($newPaths[$path]->$method)) {
                    $changes[] = [
                        'type' => 'method_removed',
                        'severity' => 'critical',
                        'message' => "HTTP method removed: {$method} {$path}",
                        'path' => $path,
                        'method' => strtoupper($method),
                        'category' => 'endpoints',
                    ];
                }

                // Check for changes in existing methods
                if (isset($pathItem->$method) && isset($newPaths[$path]->$method)) {
                    $oldOperation = $pathItem->$method;
                    $newOperation = $newPaths[$path]->$method;

                    $changes = array_merge(
                        $changes,
                        $this->compareOperation($path, $method, $oldOperation, $newOperation)
                    );
                }
            }
        }

        return $changes;
    }

    /**
     * Comparar uma operação específica (endpoint + método)
     */
    protected function compareOperation(string $path, string $method, $oldOp, $newOp): array
    {
        $changes = [];

        // Check for removed parameters
        if (isset($oldOp->parameters)) {
            $oldParams = $this->normalizeParameters($oldOp->parameters);
            $newParams = isset($newOp->parameters) ? $this->normalizeParameters($newOp->parameters) : [];

            foreach ($oldParams as $paramKey => $oldParam) {
                if (! isset($newParams[$paramKey])) {
                    $severity = ($oldParam->required ?? false) ? 'critical' : 'warning';
                    $changes[] = [
                        'type' => 'parameter_removed',
                        'severity' => $severity,
                        'message' => "Parameter removed: {$oldParam->name} from {$method} {$path}",
                        'path' => $path,
                        'method' => strtoupper($method),
                        'parameter' => $oldParam->name,
                        'category' => 'parameters',
                    ];
                }
            }

            // Check for new required parameters
            foreach ($newParams as $paramKey => $newParam) {
                if (! isset($oldParams[$paramKey]) && ($newParam->required ?? false)) {
                    $changes[] = [
                        'type' => 'required_parameter_added',
                        'severity' => 'critical',
                        'message' => "New required parameter added: {$newParam->name} to {$method} {$path}",
                        'path' => $path,
                        'method' => strtoupper($method),
                        'parameter' => $newParam->name,
                        'category' => 'parameters',
                    ];
                }
            }

            // Check for parameter type changes
            foreach ($oldParams as $paramKey => $oldParam) {
                if (isset($newParams[$paramKey])) {
                    $newParam = $newParams[$paramKey];

                    $oldType = $oldParam->schema->type ?? null;
                    $newType = $newParam->schema->type ?? null;

                    if ($oldType && $newType && $oldType !== $newType) {
                        $changes[] = [
                            'type' => 'parameter_type_changed',
                            'severity' => 'critical',
                            'message' => "Parameter type changed: {$oldParam->name} from {$oldType} to {$newType} in {$method} {$path}",
                            'path' => $path,
                            'method' => strtoupper($method),
                            'parameter' => $oldParam->name,
                            'old_type' => $oldType,
                            'new_type' => $newType,
                            'category' => 'parameters',
                        ];
                    }
                }
            }
        }

        // Check for removed response codes
        if (isset($oldOp->responses)) {
            $oldResponses = (array) $oldOp->responses;
            $newResponses = isset($newOp->responses) ? (array) $newOp->responses : [];

            foreach ($oldResponses as $statusCode => $response) {
                if (strpos($statusCode, '2') === 0 && ! isset($newResponses[$statusCode])) {
                    $changes[] = [
                        'type' => 'success_response_removed',
                        'severity' => 'critical',
                        'message' => "Success response {$statusCode} removed from {$method} {$path}",
                        'path' => $path,
                        'method' => strtoupper($method),
                        'status_code' => $statusCode,
                        'category' => 'responses',
                    ];
                }
            }
        }

        // Check for request body changes
        if (isset($oldOp->requestBody)) {
            if (! isset($newOp->requestBody)) {
                $changes[] = [
                    'type' => 'request_body_removed',
                    'severity' => 'critical',
                    'message' => "Request body removed from {$method} {$path}",
                    'path' => $path,
                    'method' => strtoupper($method),
                    'category' => 'request_body',
                ];
            } elseif (($oldOp->requestBody->required ?? false) && ! ($newOp->requestBody->required ?? false)) {
                $changes[] = [
                    'type' => 'request_body_optional',
                    'severity' => 'info',
                    'message' => "Request body made optional in {$method} {$path}",
                    'path' => $path,
                    'method' => strtoupper($method),
                    'category' => 'request_body',
                ];
            }
        }

        return $changes;
    }

    /**
     * Normalizar parâmetros para facilitar comparação
     */
    protected function normalizeParameters($parameters): array
    {
        $normalized = [];
        foreach ($parameters as $param) {
            $key = ($param->in ?? 'query').':'.($param->name ?? 'unnamed');
            $normalized[$key] = $param;
        }

        return $normalized;
    }

    /**
     * Comparar schemas entre versões
     */
    protected function compareSchemas($oldOpenApi, $newOpenApi): array
    {
        $changes = [];

        $oldSchemas = $oldOpenApi->components->schemas ?? [];
        $newSchemas = $newOpenApi->components->schemas ?? [];

        foreach ($oldSchemas as $schemaName => $oldSchema) {
            if (! isset($newSchemas[$schemaName])) {
                $changes[] = [
                    'type' => 'schema_removed',
                    'severity' => 'warning',
                    'message' => "Schema removed: {$schemaName}",
                    'schema' => $schemaName,
                    'category' => 'schemas',
                ];

                continue;
            }

            $newSchema = $newSchemas[$schemaName];

            // Check for removed required fields
            if (isset($oldSchema->required) && is_array($oldSchema->required)) {
                $newRequired = isset($newSchema->required) && is_array($newSchema->required)
                    ? $newSchema->required
                    : [];

                foreach ($oldSchema->required as $field) {
                    if (! in_array($field, $newRequired)) {
                        $changes[] = [
                            'type' => 'required_field_removed',
                            'severity' => 'warning',
                            'message' => "Required field '{$field}' removed from schema {$schemaName}",
                            'schema' => $schemaName,
                            'field' => $field,
                            'category' => 'schemas',
                        ];
                    }
                }

                // Check for new required fields
                foreach ($newRequired as $field) {
                    if (! in_array($field, $oldSchema->required)) {
                        $changes[] = [
                            'type' => 'required_field_added',
                            'severity' => 'critical',
                            'message' => "New required field '{$field}' added to schema {$schemaName}",
                            'schema' => $schemaName,
                            'field' => $field,
                            'category' => 'schemas',
                        ];
                    }
                }
            }

            // Check for property type changes
            if (isset($oldSchema->properties) && isset($newSchema->properties)) {
                foreach ($oldSchema->properties as $propName => $oldProp) {
                    if (isset($newSchema->properties->$propName)) {
                        $newProp = $newSchema->properties->$propName;

                        $oldType = $oldProp->type ?? null;
                        $newType = $newProp->type ?? null;

                        if ($oldType && $newType && $oldType !== $newType) {
                            $changes[] = [
                                'type' => 'property_type_changed',
                                'severity' => 'critical',
                                'message' => "Property '{$propName}' type changed from {$oldType} to {$newType} in schema {$schemaName}",
                                'schema' => $schemaName,
                                'field' => $propName,
                                'old_type' => $oldType,
                                'new_type' => $newType,
                                'category' => 'schemas',
                            ];
                        }
                    }
                }
            }
        }

        return $changes;
    }

    /**
     * Comparar autenticação entre versões
     */
    protected function compareAuthentication($oldOpenApi, $newOpenApi): array
    {
        $changes = [];

        $oldSecurity = $oldOpenApi->security ?? [];
        $newSecurity = $newOpenApi->security ?? [];

        // Check if authentication was added to previously open API
        if (empty($oldSecurity) && ! empty($newSecurity)) {
            $changes[] = [
                'type' => 'authentication_added',
                'severity' => 'critical',
                'message' => 'Authentication requirement added to previously open API',
                'category' => 'security',
            ];
        }

        // Check if authentication schemes changed
        $oldSchemes = $oldOpenApi->components->securitySchemes ?? [];
        $newSchemes = $newOpenApi->components->securitySchemes ?? [];

        foreach ($oldSchemes as $schemeName => $oldScheme) {
            if (! isset($newSchemes[$schemeName])) {
                $changes[] = [
                    'type' => 'security_scheme_removed',
                    'severity' => 'critical',
                    'message' => "Security scheme removed: {$schemeName}",
                    'scheme' => $schemeName,
                    'category' => 'security',
                ];
            }
        }

        return $changes;
    }

    /**
     * Agrupar breaking changes por categoria
     */
    public function groupByCategory(array $breakingChanges): array
    {
        $grouped = [];

        foreach ($breakingChanges as $change) {
            $category = $change['category'] ?? 'other';
            if (! isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $change;
        }

        return $grouped;
    }

    /**
     * Contar breaking changes por severidade
     */
    public function countBySeverity(array $breakingChanges): array
    {
        $counts = [
            'critical' => 0,
            'warning' => 0,
            'info' => 0,
        ];

        foreach ($breakingChanges as $change) {
            $severity = $change['severity'] ?? 'info';
            if (isset($counts[$severity])) {
                $counts[$severity]++;
            }
        }

        return $counts;
    }
}
