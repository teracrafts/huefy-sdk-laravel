<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Huefy API Key
    |--------------------------------------------------------------------------
    |
    | Your Huefy API key used to authenticate requests to the Huefy API.
    | You can find this in your Huefy dashboard under API Settings.
    |
    */

    'api_key' => env('HUEFY_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Huefy API. You typically don't need to change this
    | unless you're using a custom Huefy installation.
    |
    */

    'base_url' => env('HUEFY_BASE_URL', 'https://api.huefy.com/api/v1/sdk'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout for API requests in seconds. Increase this if you're sending
    | large bulk emails or experiencing slow network conditions.
    |
    */

    'timeout' => env('HUEFY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Attempts
    |--------------------------------------------------------------------------
    |
    | Number of times to retry failed requests. The package uses exponential
    | backoff for retries. Set to 0 to disable retries.
    |
    */

    'retry_attempts' => env('HUEFY_RETRY_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    |
    | The default email provider to use when none is specified.
    | Available providers: ses, sendgrid, mailgun, mailchimp
    |
    */

    'default_provider' => env('HUEFY_DEFAULT_PROVIDER', 'ses'),

    /*
    |--------------------------------------------------------------------------
    | Mail Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Laravel's mail system integration.
    |
    */

    'mail' => [
        /*
        | Default template key to use when sending emails through Laravel's
        | mail system without specifying a template.
        */
        'default_template_key' => env('HUEFY_DEFAULT_TEMPLATE_KEY'),

        /*
        | Whether to automatically extract email data from Mailable classes.
        | When enabled, the package will try to extract template data from
        | the mailable's public properties and view data.
        */
        'auto_extract_data' => env('HUEFY_AUTO_EXTRACT_DATA', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Integration
    |--------------------------------------------------------------------------
    |
    | Configuration for Laravel's notification system integration.
    |
    */

    'notifications' => [
        /*
        | Default template key to use for notifications when none is specified.
        */
        'default_template_key' => env('HUEFY_NOTIFICATION_TEMPLATE_KEY'),

        /*
        | Whether to include notification data in the template data.
        */
        'include_notification_data' => env('HUEFY_INCLUDE_NOTIFICATION_DATA', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for logging Huefy API interactions.
    |
    */

    'logging' => [
        /*
        | Whether to log successful email sends.
        */
        'log_successful_sends' => env('HUEFY_LOG_SUCCESS', false),

        /*
        | Whether to log failed email sends.
        */
        'log_failed_sends' => env('HUEFY_LOG_FAILURES', true),

        /*
        | Log channel to use for Huefy logs.
        */
        'channel' => env('HUEFY_LOG_CHANNEL', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for client-side rate limiting to prevent hitting API limits.
    |
    */

    'rate_limiting' => [
        /*
        | Whether to enable client-side rate limiting.
        */
        'enabled' => env('HUEFY_RATE_LIMITING_ENABLED', true),

        /*
        | Maximum requests per minute.
        */
        'requests_per_minute' => env('HUEFY_REQUESTS_PER_MINUTE', 60),
    ],

];