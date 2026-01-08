<?php

namespace Tests\Feature;

use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContractVersionUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    /** @test */
    public function pode_acessar_pagina_de_upload()
    {
        $contract = Contract::factory()->create();

        $response = $this->get(route('contract-versions.create', $contract));

        $response->assertOk();
        $response->assertSee('Upload Nova Versão');
        $response->assertSee($contract->title);
    }

    /** @test */
    public function pode_fazer_upload_de_arquivo_yaml_valido()
    {
        $contract = Contract::factory()->create();

        $yamlContent = <<<'YAML'
openapi: 3.0.0
info:
  title: Test API
  version: 1.0.0
paths:
  /test:
    get:
      summary: Test endpoint
      responses:
        '200':
          description: Success
YAML;

        $file = UploadedFile::fake()->createWithContent('openapi.yaml', $yamlContent);

        $response = $this->post(route('contract-versions.store', $contract), [
            'version' => '1.0.0',
            'file' => $file,
        ]);

        $response->assertRedirect(route('contracts.show', $contract));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contract_versions', [
            'contract_id' => $contract->id,
            'version' => '1.0.0',
            'status' => 'pending',
        ]);

        $version = $contract->versions()->first();
        $this->assertNotNull($version->checksum);
        $this->assertNotNull($version->metadata);
        $this->assertEquals('3.0.0', $version->metadata['openapi']);
        $this->assertEquals('Test API', $version->metadata['title']);
        $this->assertEquals(1, $version->metadata['paths_count']);

        Storage::assertExists($version->file_path);
    }

    /** @test */
    public function pode_fazer_upload_de_arquivo_json_valido()
    {
        $contract = Contract::factory()->create();

        $jsonContent = json_encode([
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'JSON API',
                'version' => '2.0.0',
            ],
            'paths' => [
                '/users' => [
                    'get' => [
                        'summary' => 'List users',
                        'responses' => [
                            '200' => ['description' => 'Success'],
                        ],
                    ],
                ],
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('openapi.json', $jsonContent);

        $response = $this->post(route('contract-versions.store', $contract), [
            'version' => '2.0.0',
            'file' => $file,
        ]);

        $response->assertRedirect(route('contracts.show', $contract));

        $version = $contract->versions()->first();
        $this->assertEquals('JSON API', $version->metadata['title']);
        $this->assertEquals('3.0.0', $version->metadata['openapi']);
    }

    /** @test */
    public function nao_permite_versao_duplicada()
    {
        $contract = Contract::factory()
            ->hasVersions(1, ['version' => '1.0.0'])
            ->create();

        $file = UploadedFile::fake()->createWithContent('openapi.yaml', 'openapi: 3.0.0');

        $response = $this->post(route('contract-versions.store', $contract), [
            'version' => '1.0.0',
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('version');
        $this->assertEquals(1, $contract->versions()->count());
    }

    /** @test */
    public function valida_formato_de_versao_semver()
    {
        $contract = Contract::factory()->create();
        $file = UploadedFile::fake()->createWithContent('openapi.yaml', 'openapi: 3.0.0');

        $response = $this->post(route('contract-versions.store', $contract), [
            'version' => 'invalid-version',
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('version');
    }

    /** @test */
    public function rejeita_arquivo_com_formato_invalido()
    {
        $contract = Contract::factory()->create();

        $file = UploadedFile::fake()->createWithContent('invalid.txt', 'not a valid openapi file');

        $response = $this->post(route('contract-versions.store', $contract), [
            'version' => '1.0.0',
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    /** @test */
    public function rejeita_arquivo_openapi_invalido()
    {
        $contract = Contract::factory()->create();

        $invalidYaml = <<<'YAML'
this_is: not
a_valid: openapi
specification: true
YAML;

        $file = UploadedFile::fake()->createWithContent('invalid.yaml', $invalidYaml);

        $response = $this->post(route('contract-versions.store', $contract), [
            'version' => '1.0.0',
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertEquals(0, $contract->versions()->count());
    }

    /** @test */
    public function pode_baixar_arquivo_de_versao()
    {
        $contract = Contract::factory()->create();

        $content = 'openapi: 3.0.0';
        Storage::put('test.yaml', $content);

        $version = $contract->versions()->create([
            'version' => '1.0.0',
            'file_path' => 'test.yaml',
            'checksum' => hash('sha256', $content),
            'status' => 'validated',
            'metadata' => ['openapi' => '3.0.0'],
        ]);

        $response = $this->get(route('contract-versions.download', $version));

        $response->assertOk();
        $response->assertDownload();
    }

    /** @test */
    public function retorna_404_ao_baixar_arquivo_inexistente()
    {
        $version = Contract::factory()
            ->hasVersions(1, [
                'file_path' => 'nonexistent.yaml',
                'checksum' => 'abc123',
            ])
            ->create()
            ->versions()
            ->first();

        $response = $this->get(route('contract-versions.download', $version));

        $response->assertNotFound();
    }
}
