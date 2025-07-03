<?php

namespace TeraCrafts\HuefyLaravel\Commands;

use Illuminate\Console\Command;
use TeraCrafts\HuefyLaravel\Exceptions\HuefyException;
use TeraCrafts\HuefyLaravel\HuefyClient;

class HuefyHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'huefy:health';

    /**
     * The console command description.
     */
    protected $description = 'Check the health status of the Huefy API';

    /**
     * Execute the console command.
     */
    public function handle(HuefyClient $huefy): int
    {
        $this->info('Checking Huefy API health...');

        try {
            $health = $huefy->healthCheck();

            $this->newLine();
            $this->info('✅ Huefy API is healthy!');
            $this->newLine();

            $this->line('<fg=cyan>Health Status:</fg=cyan>');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Status', $health['status'] ?? 'Unknown'],
                    ['Version', $health['version'] ?? 'Unknown'],
                    ['Uptime', $this->formatUptime($health['uptime'] ?? 0)],
                    ['Timestamp', $health['timestamp'] ?? 'Unknown'],
                ]
            );

            if (isset($health['providers'])) {
                $this->newLine();
                $this->line('<fg=cyan>Available Providers:</fg=cyan>');
                foreach ($health['providers'] as $provider => $status) {
                    $icon = $status === 'healthy' ? '✅' : '❌';
                    $this->line("  {$icon} {$provider}: {$status}");
                }
            }

            return self::SUCCESS;

        } catch (HuefyException $e) {
            $this->newLine();
            $this->error('❌ Huefy API health check failed!');
            $this->newLine();
            $this->line("<fg=red>Error:</fg=red> {$e->getMessage()}");
            
            if ($e->getCode()) {
                $this->line("<fg=red>Code:</fg=red> {$e->getCode()}");
            }

            return self::FAILURE;
        }
    }

    /**
     * Format uptime seconds into human readable format
     */
    private function formatUptime(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        if ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return "{$minutes} minutes";
        }

        if ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return "{$hours}h {$minutes}m";
        }

        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        return "{$days}d {$hours}h";
    }
}