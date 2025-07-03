<?php

namespace TeraCrafts\HuefyLaravel\Commands;

use Illuminate\Console\Command;
use TeraCrafts\HuefyLaravel\Exceptions\HuefyException;
use TeraCrafts\HuefyLaravel\HuefyClient;

class HuefyProvidersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'huefy:providers';

    /**
     * The console command description.
     */
    protected $description = 'List available email providers in Huefy';

    /**
     * Execute the console command.
     */
    public function handle(HuefyClient $huefy): int
    {
        $this->info('Fetching available email providers...');

        try {
            $providers = $huefy->getProviders();

            $this->newLine();
            $this->info('📧 Available Email Providers:');
            $this->newLine();

            if (empty($providers)) {
                $this->warn('No providers found.');
                return self::SUCCESS;
            }

            $tableData = [];
            foreach ($providers as $provider) {
                $tableData[] = [
                    $provider['name'] ?? 'Unknown',
                    $provider['status'] ?? 'Unknown',
                    $provider['description'] ?? 'No description',
                    $provider['features'] ? implode(', ', $provider['features']) : 'None listed',
                ];
            }

            $this->table(
                ['Provider', 'Status', 'Description', 'Features'],
                $tableData
            );

            $defaultProvider = config('huefy.default_provider', 'ses');
            $this->newLine();
            $this->line("<fg=cyan>Default Provider:</fg=cyan> {$defaultProvider}");

            return self::SUCCESS;

        } catch (HuefyException $e) {
            $this->newLine();
            $this->error('❌ Failed to fetch providers!');
            $this->newLine();
            $this->line("<fg=red>Error:</fg=red> {$e->getMessage()}");

            if ($e->getCode()) {
                $this->line("<fg=red>Code:</fg=red> {$e->getCode()}");
            }

            return self::FAILURE;
        }
    }
}