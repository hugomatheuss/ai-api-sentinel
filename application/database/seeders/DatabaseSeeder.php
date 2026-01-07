<?php

namespace Database\Seeders;

use App\Models\Api;
use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\User;
use App\Models\ValidationReport;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Criar usuário de teste
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Criar 5 APIs ativas com contratos e versões
        $activeApis = Api::factory(5)->active()->create();

        foreach ($activeApis as $api) {
            $contracts = Contract::factory(2)->for($api)->create();

            foreach ($contracts as $contract) {
                $versions = ContractVersion::factory(3)
                    ->validated()
                    ->for($contract)
                    ->create();

                // Criar relatório de validação para cada versão
                foreach ($versions as $version) {
                    ValidationReport::factory()
                        ->pass()
                        ->create(['contract_version_id' => $version->id]);
                }
            }
        }

        // Criar 2 APIs deprecated com menos conteúdo
        $deprecatedApis = Api::factory(2)->deprecated()->create();

        foreach ($deprecatedApis as $api) {
            $contract = Contract::factory()->for($api)->create();
            ContractVersion::factory(2)
                ->validated()
                ->for($contract)
                ->create();
        }

        // Criar exemplo específico com validation failed
        $api = Api::factory()->active()->create(['name' => 'Payment API']);
        $contract = Contract::factory()->for($api)->create(['title' => 'Payment Service Contract']);
        $version = ContractVersion::factory()->pending()->for($contract)->create(['version' => '1.0.0']);
        ValidationReport::factory()->fail()->create(['contract_version_id' => $version->id]);
    }
}
