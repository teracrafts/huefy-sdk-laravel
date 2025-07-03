<?php

namespace TeraCrafts\HuefyLaravel\Commands;

use Illuminate\Console\Command;
use TeraCrafts\HuefyLaravel\Exceptions\HuefyException;
use TeraCrafts\HuefyLaravel\HuefyClient;

class HuefySendCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'huefy:send 
                            {template : The template key to use}
                            {recipient : The recipient email address}
                            {--data= : JSON string of template data}
                            {--provider= : Email provider to use (ses, sendgrid, mailgun, mailchimp)}
                            {--file= : Path to JSON file containing template data}';

    /**
     * The console command description.
     */
    protected $description = 'Send an email using a Huefy template';

    /**
     * Execute the console command.
     */
    public function handle(HuefyClient $huefy): int
    {
        $templateKey = $this->argument('template');
        $recipient = $this->argument('recipient');
        $provider = $this->option('provider');

        // Get template data
        $data = $this->getTemplateData();
        if ($data === null) {
            return self::FAILURE;
        }

        $this->info("Sending email with template '{$templateKey}' to {$recipient}...");

        try {
            $response = $huefy->sendEmail($templateKey, $data, $recipient, $provider);

            $this->newLine();
            $this->info('✅ Email sent successfully!');
            $this->newLine();

            $this->table(
                ['Property', 'Value'],
                [
                    ['Message ID', $response['message_id'] ?? 'N/A'],
                    ['Provider', $response['provider'] ?? 'Unknown'],
                    ['Status', $response['status'] ?? 'Unknown'],
                    ['Timestamp', $response['timestamp'] ?? 'N/A'],
                ]
            );

            return self::SUCCESS;

        } catch (HuefyException $e) {
            $this->newLine();
            $this->error('❌ Failed to send email!');
            $this->newLine();
            $this->line("<fg=red>Error:</fg=red> {$e->getMessage()}");

            if ($e->getCode()) {
                $this->line("<fg=red>Code:</fg=red> {$e->getCode()}");
            }

            return self::FAILURE;
        }
    }

    /**
     * Get template data from options or user input
     */
    private function getTemplateData(): ?array
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
        $this->line('No template data provided. Enter data interactively:');
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
            $this->warn('No template data provided. Sending with empty data.');
        }

        return $data;
    }
}