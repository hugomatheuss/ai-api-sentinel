<?php

namespace App\Services;

use cebe\openapi\exceptions\IOException;
use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;

/**
 * Serviço responsável por parsing e extração de dados de contratos OpenAPI.
 *
 * Extrai endpoints, parâmetros, respostas e outras informações estruturais
 * de especificações OpenAPI 3.x e Swagger 2.0.
 */
class ContractParserService
{
    /**
     * Parse arquivo OpenAPI e retornar objeto estruturado
     */
    public function parse(string $filePath, string $extension): OpenApi
    {
        try {
            if (in_array(strtolower($extension), ['yaml', 'yml'])) {
                return Reader::readFromYamlFile($filePath);
            }

            return Reader::readFromJsonFile($filePath);
        } catch (IOException|TypeErrorException|UnresolvableReferenceException $e) {
            throw new \Exception('Erro ao fazer parse do arquivo OpenAPI: '.$e->getMessage());
        }
    }

    /**
     * Extrair todos os endpoints do contrato OpenAPI
     */
    public function extractEndpoints(OpenApi $openapi): array
    {
        if (! isset($openapi->paths)) {
            return [];
        }

        $endpoints = [];

        foreach ($openapi->paths as $path => $pathItem) {
            // Iterar sobre os métodos HTTP (get, post, put, delete, patch, etc)
            $httpMethods = ['get', 'post', 'put', 'delete', 'patch', 'head', 'options', 'trace'];

            foreach ($httpMethods as $method) {
                if (! isset($pathItem->$method)) {
                    continue;
                }

                $operation = $pathItem->$method;

                $endpoints[] = [
                    'path' => $path,
                    'method' => strtoupper($method),
                    'summary' => $operation->summary ?? null,
                    'description' => $operation->description ?? null,
                    'parameters' => $this->extractParameters($operation),
                    'responses' => $this->extractResponses($operation),
                    'request_body' => $this->extractRequestBody($operation),
                    'security' => $this->extractSecurity($operation),
                ];
            }
        }

        return $endpoints;
    }

    /**
     * Extrair parâmetros de uma operação
     */
    protected function extractParameters($operation): ?array
    {
        if (! isset($operation->parameters) || empty($operation->parameters)) {
            return null;
        }

        $parameters = [];

        foreach ($operation->parameters as $param) {
            $parameters[] = [
                'name' => $param->name ?? null,
                'in' => $param->in ?? null,
                'description' => $param->description ?? null,
                'required' => $param->required ?? false,
                'schema' => $this->extractSchema($param->schema ?? null),
            ];
        }

        return $parameters;
    }

    /**
     * Extrair respostas de uma operação
     */
    protected function extractResponses($operation): ?array
    {
        if (! isset($operation->responses) || empty($operation->responses)) {
            return null;
        }

        $responses = [];

        foreach ($operation->responses as $statusCode => $response) {
            $responses[$statusCode] = [
                'description' => $response->description ?? null,
                'content' => $this->extractContent($response->content ?? null),
            ];
        }

        return $responses;
    }

    /**
     * Extrair request body de uma operação
     */
    protected function extractRequestBody($operation): ?array
    {
        if (! isset($operation->requestBody)) {
            return null;
        }

        $requestBody = $operation->requestBody;

        return [
            'description' => $requestBody->description ?? null,
            'required' => $requestBody->required ?? false,
            'content' => $this->extractContent($requestBody->content ?? null),
        ];
    }

    /**
     * Extrair informações de segurança
     */
    protected function extractSecurity($operation): ?array
    {
        if (! isset($operation->security) || empty($operation->security)) {
            return null;
        }

        $security = [];

        foreach ($operation->security as $securityRequirement) {
            foreach ($securityRequirement as $name => $scopes) {
                $security[] = [
                    'name' => $name,
                    'scopes' => $scopes,
                ];
            }
        }

        return $security;
    }

    /**
     * Extrair schema de um parâmetro/campo
     */
    protected function extractSchema($schema): ?array
    {
        if (! $schema) {
            return null;
        }

        return [
            'type' => $schema->type ?? null,
            'format' => $schema->format ?? null,
            'enum' => $schema->enum ?? null,
            'default' => $schema->default ?? null,
            'example' => $schema->example ?? null,
        ];
    }

    /**
     * Extrair content types e seus schemas
     */
    protected function extractContent($content): ?array
    {
        if (! $content) {
            return null;
        }

        $extracted = [];

        foreach ($content as $mediaType => $mediaTypeObject) {
            $extracted[$mediaType] = [
                'schema' => $this->extractSchema($mediaTypeObject->schema ?? null),
            ];
        }

        return $extracted;
    }

    /**
     * Extrair metadata geral do contrato
     */
    public function extractMetadata(OpenApi $openapi): array
    {
        return [
            'openapi' => $openapi->openapi ?? $openapi->swagger ?? null,
            'title' => $openapi->info->title ?? null,
            'version' => $openapi->info->version ?? null,
            'description' => $openapi->info->description ?? null,
            'servers' => $this->extractServers($openapi),
            'paths_count' => isset($openapi->paths) ? count((array) $openapi->paths) : 0,
            'components_count' => isset($openapi->components->schemas) ? count((array) $openapi->components->schemas) : 0,
        ];
    }

    /**
     * Extrair servidores da especificação
     */
    protected function extractServers(OpenApi $openapi): array
    {
        if (! isset($openapi->servers)) {
            return [];
        }

        $servers = [];

        foreach ($openapi->servers as $server) {
            if (isset($server->url)) {
                $servers[] = [
                    'url' => $server->url,
                    'description' => $server->description ?? null,
                ];
            }
        }

        return $servers;
    }
}
