<?php

namespace TeraCrafts\HuefyLaravel\Commands;

use Illuminate\Console\Command;
use TeraCrafts\HuefyLaravel\Exceptions\HuefyException;
use TeraCrafts\HuefyLaravel\HuefyClient;

class HuefyValidateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'huefy:validate 
                            {template : The template key to validate}
                            {--data= : JSON string of test data}
                            {--file= : Path to JSON file containing test data}';

    /**
     * The console command description.
     */
    protected $description = 'Validate a Huefy template with test data';

    /**
     * Execute the console command.
     */
    public function handle(HuefyClient $huefy): int
    {
        $templateKey = $this->argument('template');

        // Get test data
        $data = $this->getTestData();
        if ($data === null) {
            return self::FAILURE;
        }

        $this->info("Validating template '{$templateKey}' with test data...");

        try {
            $isValid = $huefy->validateTemplate($templateKey, $data);

            $this->newLine();
            if ($isValid) {
                $this->info('✅ Template validation successful!');
                $this->line('The template can be rendered with the provided data.');
            } else {
                $this->warn('⚠️  Template validation failed!');
                $this->line('The template cannot be rendered with the provided data.');
            }

            return $isValid ? self::SUCCESS : self::FAILURE;

        } catch (HuefyException $e) {
            $this->newLine();
            $this->error('❌ Template validation error!');
            $this->newLine();
            $this->line("<fg=red>Error:</fg=red> {$e->getMessage()}");

            if ($e->getCode()) {
                $this->line("<fg=red>Code:</fg=red> {$e->getCode()}");
            }

            return self::FAILURE;
        }
    }

    /**
     * Get test data from options or user input
     */
    private function getTestData(): ?array
    {
        // Try to get data from file option
        if ($filePath = $this->option('file')) {
            if (!file_exists($filePath)) {
                $this->error("Data file not found: {$filePath}");
                return null;
            }

            $contents = file_get_contents($filePath);
            $data = json_decode($contents, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Invalid JSON in file: " . json_last_error_msg());
                return null;
            }

            return $data;
        }

        // Try to get data from option
        if ($dataOption = $this->option('data')) {
            $data = json_decode($dataOption, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Invalid JSON data: " . json_last_error_msg());
                return null;
            }

            return $data;
        }

        // Ask user for data interactively
        $this->line('No test data provided. Enter data interactively:');
        $this->line('Enter key-value pairs. Press Enter with empty key to finish.');
        $this->newLine();

        $data = [];
        while (true) {
            $key = $this->ask('Key (or press Enter to finish)');
            if (empty($key)) {
                break;
            }

            $value = $this->ask("Value for '{$key}'");
            
            // Try to parse as JSON for complex values
            $jsonValue = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data[$key] = $jsonValue;
            } else {
                $data[$key] = $value;
            }
        }

        if (empty($data)) {
            $this->warn('No test data provided. Validating with empty data.');
        }

        return $data;
    }
}