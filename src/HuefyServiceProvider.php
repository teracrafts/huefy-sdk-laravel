<?php

namespace TeraCrafts\HuefyLaravel;

use Illuminate\Mail\MailManager;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use TeraCrafts\HuefyLaravel\Commands\HuefyHealthCommand;
use TeraCrafts\HuefyLaravel\Commands\HuefyProvidersCommand;
use TeraCrafts\HuefyLaravel\Commands\HuefySendCommand;
use TeraCrafts\HuefyLaravel\Commands\HuefyValidateCommand;
use TeraCrafts\HuefyLaravel\Mail\HuefyTransport;
use TeraCrafts\HuefyLaravel\Notifications\HuefyChannel;

class HuefyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/huefy.php',
            'huefy'
        );

        $this->app->singleton(HuefyClient::class, function ($app) {
            $config = $app['config']['huefy'];

            return new HuefyClient(
                apiKey: $config['api_key'],
                baseUrl: $config['base_url'],
                timeout: $config['timeout'],
                retryAttempts: $config['retry_attempts']
            );
        });

        $this->app->alias(HuefyClient::class, 'huefy');

        // Register mail transport
        $this->app->resolving(MailManager::class, function (MailManager $mailManager) {
            $mailManager->extend('huefy', function (array $config) {
                return new HuefyTransport(
                    $this->app->make(HuefyClient::class),
                    $config['template_key'] ?? null,
                    $config['provider'] ?? null
                );
            });
        });

        // Register notification channel
        $this->app->resolving(ChannelManager::class, function (ChannelManager $channelManager) {
            $channelManager->extend('huefy', function () {
                return new HuefyChannel(
                    $this->app->make(HuefyClient::class)
                );
            });
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/huefy.php' => config_path('huefy.php'),
            ], 'huefy-config');

            // Register commands
            $this->commands([
                HuefyHealthCommand::class,
                HuefyProvidersCommand::class,
                HuefySendCommand::class,
                HuefyValidateCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            HuefyClient::class,
            'huefy',
        ];
    }
}