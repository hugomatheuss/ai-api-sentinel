<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Contract;
use App\Models\ContractVersion;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some existing contracts and versions for realistic logs
        $contracts = Contract::with('versions')->limit(3)->get();

        if ($contracts->isEmpty()) {
            $this->command->warn('No contracts found. Please seed contracts first.');

            return;
        }

        $logs = [];

        foreach ($contracts as $contract) {
            if ($contract->versions->isEmpty()) {
                continue;
            }

            $version = $contract->versions->first();

            // Validation logs
            $logs[] = [
                'log_name' => 'validation',
                'description' => "Contract {$contract->title} v{$version->version} validated successfully",
                'subject_type' => ContractVersion::class,
                'subject_id' => $version->id,
                'event' => 'validation_passed',
                'properties' => json_encode([
                    'contract_id' => $contract->id,
                    'version' => $version->version,
                    'error_count' => 0,
                    'warning_count' => rand(0, 5),
                    'breaking_changes_count' => 0,
                ]),
                'ip_address' => '192.168.1.'.rand(1, 254),
                'created_at' => now()->subHours(rand(1, 72)),
                'updated_at' => now()->subHours(rand(1, 72)),
            ];

            // API logs
            $logs[] = [
                'log_name' => 'api',
                'description' => "API validation request for {$contract->title}",
                'event' => 'api_request',
                'properties' => json_encode([
                    'endpoint' => '/api/v1/validate',
                    'method' => 'POST',
                    'contract_id' => $contract->id,
                ]),
                'ip_address' => '10.0.0.'.rand(1, 254),
                'created_at' => now()->subHours(rand(1, 48)),
                'updated_at' => now()->subHours(rand(1, 48)),
            ];

            // Contract logs
            $logs[] = [
                'log_name' => 'contract',
                'description' => "New version {$version->version} uploaded for {$contract->title}",
                'subject_type' => ContractVersion::class,
                'subject_id' => $version->id,
                'event' => 'version_uploaded',
                'properties' => json_encode([
                    'contract_id' => $contract->id,
                    'version' => $version->version,
                    'file_size' => rand(1000, 50000),
                ]),
                'ip_address' => '192.168.1.'.rand(1, 254),
                'created_at' => now()->subHours(rand(1, 96)),
                'updated_at' => now()->subHours(rand(1, 96)),
            ];
        }

        // Add some webhook logs
        $logs[] = [
            'log_name' => 'webhook',
            'description' => 'Webhook delivered successfully to Slack',
            'event' => 'webhook_delivered',
            'properties' => json_encode([
                'webhook_id' => 1,
                'event_type' => 'contract.validated',
                'status_code' => 200,
                'attempt' => 1,
            ]),
            'ip_address' => null,
            'created_at' => now()->subHours(rand(1, 24)),
            'updated_at' => now()->subHours(rand(1, 24)),
        ];

        $logs[] = [
            'log_name' => 'webhook',
            'description' => 'Webhook delivery failed - endpoint timeout',
            'event' => 'webhook_failed',
            'properties' => json_encode([
                'webhook_id' => 2,
                'event_type' => 'breaking_changes.detected',
                'error' => 'Connection timeout',
                'attempt' => 3,
            ]),
            'ip_address' => null,
            'created_at' => now()->subHours(rand(1, 12)),
            'updated_at' => now()->subHours(rand(1, 12)),
        ];

        // Insert all logs
        foreach ($logs as $log) {
            ActivityLog::create($log);
        }

        $this->command->info('Activity logs seeded successfully!');
    }
}
