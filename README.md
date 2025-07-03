# Huefy Laravel Package

The official Laravel package for Huefy - App Mail Templates. Provides seamless integration with Laravel's mail and notification systems, along with artisan commands and service providers for easy template-based email management.

## Installation

Install the package via Composer:

```bash
composer require teracrafts/huefy-laravel
```

### Laravel Auto-Discovery

The package uses Laravel's auto-discovery feature, so the service provider and facade are registered automatically.

### Manual Registration (if needed)

If auto-discovery is disabled, add the service provider to your `config/app.php`:

```php
'providers' => [
    // ...
    TeraCrafts\HuefyLaravel\HuefyServiceProvider::class,
],

'aliases' => [
    // ...
    'Huefy' => TeraCrafts\HuefyLaravel\Facades\Huefy::class,
],
```

### Publish Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=huefy-config
```

## Configuration

Add your Huefy API key to your `.env` file:

```env
HUEFY_API_KEY=your-huefy-api-key
HUEFY_BASE_URL=https://api.huefy.com/api/v1/sdk
HUEFY_DEFAULT_PROVIDER=ses
HUEFY_TIMEOUT=30
HUEFY_RETRY_ATTEMPTS=3
```

## Basic Usage

### Using the Facade

```php
use TeraCrafts\HuefyLaravel\Facades\Huefy;

// Send a single email
$response = Huefy::sendEmail('welcome-email', [
    'name' => 'John Doe',
    'company' => 'Acme Corp'
], 'john@example.com');

// Send with specific provider
$response = Huefy::sendEmail('newsletter', [
    'name' => 'Jane Smith',
    'unsubscribe_url' => 'https://example.com/unsubscribe'
], 'jane@example.com', 'sendgrid');

// Send bulk emails
$emails = [
    [
        'template_key' => 'welcome-email',
        'data' => ['name' => 'John'],
        'recipient' => 'john@example.com'
    ],
    [
        'template_key' => 'welcome-email', 
        'data' => ['name' => 'Jane'],
        'recipient' => 'jane@example.com',
        'provider' => 'mailgun'
    ]
];

$results = Huefy::sendBulkEmails($emails);
```

### Using Dependency Injection

```php
use TeraCrafts\HuefyLaravel\HuefyClient;

class EmailService
{
    public function __construct(private HuefyClient $huefy)
    {
    }

    public function sendWelcomeEmail(User $user): void
    {
        $this->huefy->sendEmail('welcome-email', [
            'name' => $user->name,
            'email' => $user->email,
            'login_url' => route('login')
        ], $user->email);
    }
}
```

## Laravel Mail Integration

Configure Huefy as a mail driver in `config/mail.php`:

```php
'mailers' => [
    'huefy' => [
        'transport' => 'huefy',
        'template_key' => env('HUEFY_DEFAULT_TEMPLATE_KEY'),
        'provider' => env('HUEFY_DEFAULT_PROVIDER', 'ses'),
    ],
],
```

### Using with Mailables

```php
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class WelcomeMail extends Mailable
{
    public function __construct(
        public User $user
    ) {}

    public function build()
    {
        return $this->view('emails.welcome')
                    ->with([
                        'name' => $this->user->name,
                        'login_url' => route('login')
                    ])
                    ->subject('Welcome to our platform!');
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Welcome!',
            using: [
                fn (Message $message) => $message
                    ->getHeaders()
                    ->addTextHeader('X-Template-Key', 'welcome-email')
                    ->addTextHeader('X-Template-Data', json_encode([
                        'name' => $this->user->name,
                        'login_url' => route('login')
                    ]))
                    ->addTextHeader('X-Email-Provider', 'sendgrid')
            ]
        );
    }
}

// Send the email
Mail::to('user@example.com')->send(new WelcomeMail($user));
```

## Laravel Notification Integration

### Creating a Huefy Notification

```php
use Illuminate\Notifications\Notification;
use TeraCrafts\HuefyLaravel\Notifications\HuefyChannel;
use TeraCrafts\HuefyLaravel\Notifications\HuefyMessage;

class WelcomeNotification extends Notification
{
    public function __construct(
        private User $user
    ) {}

    public function via($notifiable): array
    {
        return [HuefyChannel::class];
    }

    public function toHuefy($notifiable): HuefyMessage
    {
        return HuefyMessage::create('welcome-email')
            ->data([
                'name' => $this->user->name,
                'email' => $this->user->email,
                'activation_url' => route('activation', $this->user->id)
            ])
            ->provider('sendgrid');
    }
}

// Send the notification
$user->notify(new WelcomeNotification($user));
```

### Using Route Notification

```php
// In your User model
public function routeNotificationForHuefy()
{
    return $this->email;
}

// Or return an array for more complex routing
public function routeNotificationForHuefy()
{
    return [
        'email' => $this->email,
        'name' => $this->name
    ];
}
```

## Artisan Commands

The package provides several artisan commands for managing emails:

### Health Check

Check the Huefy API health status:

```bash
php artisan huefy:health
```

### Send Email

Send an email using a template:

```bash
# Basic usage
php artisan huefy:send welcome-email user@example.com

# With inline data
php artisan huefy:send welcome-email user@example.com --data='{"name":"John","company":"Acme"}'

# With data from file
php artisan huefy:send welcome-email user@example.com --file=data.json

# With specific provider
php artisan huefy:send newsletter user@example.com --provider=sendgrid --data='{"name":"Jane"}'
```

### Validate Template

Validate a template with test data:

```bash
# Interactive validation
php artisan huefy:validate welcome-email

# With inline data
php artisan huefy:validate welcome-email --data='{"name":"Test User"}'

# With data from file
php artisan huefy:validate welcome-email --file=test-data.json
```

### List Providers

List available email providers:

```bash
php artisan huefy:providers
```

## Advanced Usage

### Custom Mail Transport

Create a custom mailable that uses Huefy transport:

```php
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

class CustomHuefyMail extends Mailable
{
    public function __construct(
        private string $templateKey,
        private array $templateData,
        private ?string $provider = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Custom Huefy Email',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.empty', // Dummy view since we're using templates
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Template-Key' => $this->templateKey,
                'X-Template-Data' => json_encode($this->templateData),
                'X-Email-Provider' => $this->provider ?: config('huefy.default_provider'),
            ],
        );
    }
}
```

### Bulk Email Processing

```php
use TeraCrafts\HuefyLaravel\Facades\Huefy;

class NewsletterService
{
    public function sendNewsletter(Collection $subscribers, string $templateKey): array
    {
        $emails = $subscribers->map(function ($subscriber) use ($templateKey) {
            return [
                'template_key' => $templateKey,
                'data' => [
                    'name' => $subscriber->name,
                    'preferences_url' => route('preferences', $subscriber->id),
                    'unsubscribe_url' => route('unsubscribe', $subscriber->token),
                ],
                'recipient' => $subscriber->email,
                'provider' => $subscriber->preferred_provider ?? 'ses',
            ];
        })->toArray();

        return Huefy::sendBulkEmails($emails);
    }
}
```

### Error Handling

```php
use TeraCrafts\HuefyLaravel\Exceptions\HuefyException;
use TeraCrafts\HuefyLaravel\Exceptions\TemplateNotFoundException;
use TeraCrafts\HuefyLaravel\Exceptions\ValidationException;
use TeraCrafts\HuefyLaravel\Facades\Huefy;

try {
    Huefy::sendEmail('welcome-email', $data, $email);
} catch (TemplateNotFoundException $e) {
    Log::error('Template not found', [
        'template' => $e->getTemplateKey(),
        'message' => $e->getMessage()
    ]);
} catch (ValidationException $e) {
    Log::error('Validation failed', [
        'errors' => $e->getErrors(),
        'message' => $e->getMessage()
    ]);
} catch (HuefyException $e) {
    Log::error('Huefy API error', [
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
        'context' => $e->getContext()
    ]);
}
```

### Queue Integration

```php
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TeraCrafts\HuefyLaravel\Facades\Huefy;

class SendHuefyEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $templateKey,
        private array $data,
        private string $recipient,
        private ?string $provider = null
    ) {}

    public function handle(): void
    {
        Huefy::sendEmail(
            $this->templateKey,
            $this->data,
            $this->recipient,
            $this->provider
        );
    }
}

// Dispatch the job
SendHuefyEmailJob::dispatch('welcome-email', $data, $email)->onQueue('emails');
```

## Configuration Reference

The `config/huefy.php` file contains all configuration options:

```php
return [
    // API Configuration
    'api_key' => env('HUEFY_API_KEY'),
    'base_url' => env('HUEFY_BASE_URL', 'https://api.huefy.com/api/v1/sdk'),
    'timeout' => env('HUEFY_TIMEOUT', 30),
    'retry_attempts' => env('HUEFY_RETRY_ATTEMPTS', 3),
    'default_provider' => env('HUEFY_DEFAULT_PROVIDER', 'ses'),

    // Mail Integration
    'mail' => [
        'default_template_key' => env('HUEFY_DEFAULT_TEMPLATE_KEY'),
        'auto_extract_data' => env('HUEFY_AUTO_EXTRACT_DATA', true),
    ],

    // Notification Integration
    'notifications' => [
        'default_template_key' => env('HUEFY_NOTIFICATION_TEMPLATE_KEY'),
        'include_notification_data' => env('HUEFY_INCLUDE_NOTIFICATION_DATA', true),
    ],

    // Logging
    'logging' => [
        'log_successful_sends' => env('HUEFY_LOG_SUCCESS', false),
        'log_failed_sends' => env('HUEFY_LOG_FAILURES', true),
        'channel' => env('HUEFY_LOG_CHANNEL', 'default'),
    ],

    // Rate Limiting
    'rate_limiting' => [
        'enabled' => env('HUEFY_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('HUEFY_REQUESTS_PER_MINUTE', 60),
    ],
];
```

## Testing

```php
use TeraCrafts\HuefyLaravel\Facades\Huefy;

// Mock the Huefy facade for testing
public function test_sends_welcome_email()
{
    Huefy::shouldReceive('sendEmail')
        ->once()
        ->with('welcome-email', ['name' => 'John'], 'john@example.com', null)
        ->andReturn(['message_id' => 'test-123', 'status' => 'sent']);

    $result = $this->emailService->sendWelcome('john@example.com', 'John');

    $this->assertTrue($result);
}
```

## Requirements

- PHP 8.1 or higher
- Laravel 9.0, 10.0, or 11.0
- Guzzle HTTP client 7.0+

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

- **Documentation**: [https://docs.huefy.dev/sdk/laravel](https://docs.huefy.dev/sdk/laravel)
- **Issues**: [GitHub Issues](https://github.com/teracrafts/huefy-sdk-laravel/issues)
- **Email**: [hello@huefy.dev](mailto:hello@huefy.dev)