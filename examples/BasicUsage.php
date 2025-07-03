<?php

/**
 * Basic Huefy Laravel Usage Examples
 * 
 * This file demonstrates various ways to use the Huefy Laravel package
 * for sending emails with templates.
 */

use TeraCrafts\HuefyLaravel\Facades\Huefy;
use TeraCrafts\HuefyLaravel\HuefyClient;
use TeraCrafts\HuefyLaravel\Exceptions\HuefyException;

// Example 1: Using the Facade
class BasicEmailExample
{
    public function sendWelcomeEmail()
    {
        try {
            $response = Huefy::sendEmail('welcome-email', [
                'name' => 'John Doe',
                'company' => 'Acme Corporation',
                'activation_link' => 'https://app.example.com/activate/abc123'
            ], 'john.doe@example.com');

            return [
                'success' => true,
                'message_id' => $response['message_id'],
                'provider' => $response['provider']
            ];

        } catch (HuefyException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function sendWithSpecificProvider()
    {
        return Huefy::sendEmail('newsletter', [
            'subscriber_name' => 'Jane Smith',
            'newsletter_title' => 'Weekly Updates',
            'unsubscribe_url' => 'https://app.example.com/unsubscribe/xyz789'
        ], 'jane.smith@example.com', 'sendgrid');
    }

    public function sendBulkEmails()
    {
        $emails = [
            [
                'template_key' => 'welcome-email',
                'data' => ['name' => 'Alice Brown', 'company' => 'TechCorp'],
                'recipient' => 'alice@techcorp.com'
            ],
            [
                'template_key' => 'welcome-email',
                'data' => ['name' => 'Bob Wilson', 'company' => 'StartupXYZ'],
                'recipient' => 'bob@startupxyz.com',
                'provider' => 'mailgun'
            ],
            [
                'template_key' => 'newsletter',
                'data' => ['name' => 'Carol Davis', 'newsletter_title' => 'Monthly Digest'],
                'recipient' => 'carol@example.com',
                'provider' => 'ses'
            ]
        ];

        return Huefy::sendBulkEmails($emails);
    }
}

// Example 2: Using Dependency Injection
class EmailService
{
    public function __construct(
        private HuefyClient $huefy
    ) {}

    public function sendPasswordReset(string $email, string $token): array
    {
        return $this->huefy->sendEmail('password-reset', [
            'reset_link' => route('password.reset', ['token' => $token]),
            'expires_at' => now()->addHours(2)->format('M j, Y g:i A'),
            'support_email' => config('mail.support_address')
        ], $email);
    }

    public function validateTemplate(string $templateKey): bool
    {
        return $this->huefy->validateTemplate($templateKey, [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'company' => 'Test Company'
        ]);
    }

    public function checkApiHealth(): array
    {
        return $this->huefy->healthCheck();
    }

    public function getAvailableProviders(): array
    {
        return $this->huefy->getProviders();
    }
}

// Example 3: Error Handling
class RobustEmailService
{
    public function __construct(
        private HuefyClient $huefy
    ) {}

    public function sendEmailWithErrorHandling(
        string $templateKey,
        array $data,
        string $recipient,
        ?string $provider = null
    ): array {
        try {
            $response = $this->huefy->sendEmail($templateKey, $data, $recipient, $provider);

            \Log::info('Email sent successfully', [
                'template' => $templateKey,
                'recipient' => $recipient,
                'message_id' => $response['message_id'] ?? null
            ]);

            return [
                'success' => true,
                'data' => $response
            ];

        } catch (\TeraCrafts\HuefyLaravel\Exceptions\TemplateNotFoundException $e) {
            \Log::error('Template not found', [
                'template' => $templateKey,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'template_not_found',
                'message' => "Template '{$templateKey}' was not found"
            ];

        } catch (\TeraCrafts\HuefyLaravel\Exceptions\ValidationException $e) {
            \Log::error('Validation error', [
                'template' => $templateKey,
                'errors' => $e->getErrors(),
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'validation_failed',
                'message' => $e->getMessage(),
                'validation_errors' => $e->getErrors()
            ];

        } catch (HuefyException $e) {
            \Log::error('Huefy API error', [
                'template' => $templateKey,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return [
                'success' => false,
                'error' => 'api_error',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];

        } catch (\Exception $e) {
            \Log::error('Unexpected error', [
                'template' => $templateKey,
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'unexpected_error',
                'message' => 'An unexpected error occurred'
            ];
        }
    }
}