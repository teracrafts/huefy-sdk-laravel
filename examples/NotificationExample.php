<?php

/**
 * Laravel Notification Integration Examples
 * 
 * This file demonstrates how to use Huefy with Laravel's notification system.
 */

use Illuminate\Notifications\Notification;
use TeraCrafts\HuefyLaravel\Notifications\HuefyChannel;
use TeraCrafts\HuefyLaravel\Notifications\HuefyMessage;

// Example 1: Welcome Notification
class WelcomeNotification extends Notification
{
    public function __construct(
        private string $activationToken
    ) {}

    public function via($notifiable): array
    {
        return [HuefyChannel::class];
    }

    public function toHuefy($notifiable): HuefyMessage
    {
        return HuefyMessage::create('welcome-email')
            ->data([
                'name' => $notifiable->name,
                'email' => $notifiable->email,
                'activation_url' => route('activate', $this->activationToken),
                'company_name' => config('app.name'),
                'support_email' => config('mail.support_address')
            ]);
    }
}

// Example 2: Password Reset Notification  
class PasswordResetNotification extends Notification
{
    public function __construct(
        private string $token
    ) {}

    public function via($notifiable): array
    {
        return [HuefyChannel::class];
    }

    public function toHuefy($notifiable): HuefyMessage
    {
        return HuefyMessage::create('password-reset')
            ->data([
                'name' => $notifiable->name,
                'reset_url' => route('password.reset', [
                    'token' => $this->token,
                    'email' => $notifiable->email
                ]),
                'expires_in' => '2 hours',
                'app_name' => config('app.name')
            ])
            ->provider('sendgrid'); // Use specific provider
    }
}

// Example 3: Order Confirmation Notification
class OrderConfirmationNotification extends Notification
{
    public function __construct(
        private $order
    ) {}

    public function via($notifiable): array
    {
        return [HuefyChannel::class];
    }

    public function toHuefy($notifiable): HuefyMessage
    {
        return HuefyMessage::create('order-confirmation')
            ->data([
                'customer_name' => $notifiable->name,
                'order_number' => $this->order->number,
                'order_date' => $this->order->created_at->format('M j, Y'),
                'order_total' => number_format($this->order->total, 2),
                'items' => $this->order->items->map(function ($item) {
                    return [
                        'name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'price' => number_format($item->price, 2)
                    ];
                })->toArray(),
                'shipping_address' => [
                    'name' => $this->order->shipping_name,
                    'address' => $this->order->shipping_address,
                    'city' => $this->order->shipping_city,
                    'state' => $this->order->shipping_state,
                    'zip' => $this->order->shipping_zip
                ],
                'tracking_url' => route('order.tracking', $this->order->tracking_number)
            ]);
    }
}

// Example 4: Multi-channel Notification with Huefy
class InvoiceNotification extends Notification
{
    public function __construct(
        private $invoice
    ) {}

    public function via($notifiable): array
    {
        return ['mail', HuefyChannel::class, 'database'];
    }

    public function toMail($notifiable)
    {
        // Fallback Laravel mail
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Invoice Ready')
            ->line('Your invoice is ready for download.')
            ->action('View Invoice', route('invoices.show', $this->invoice->id));
    }

    public function toHuefy($notifiable): HuefyMessage
    {
        return HuefyMessage::create('invoice-ready')
            ->data([
                'customer_name' => $notifiable->name,
                'invoice_number' => $this->invoice->number,
                'invoice_date' => $this->invoice->date->format('M j, Y'),
                'due_date' => $this->invoice->due_date->format('M j, Y'),
                'amount_due' => number_format($this->invoice->amount, 2),
                'invoice_url' => route('invoices.show', $this->invoice->id),
                'payment_url' => route('invoices.pay', $this->invoice->id)
            ])
            ->provider('mailgun');
    }

    public function toArray($notifiable): array
    {
        // Database notification data
        return [
            'type' => 'invoice_ready',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'amount' => $this->invoice->amount
        ];
    }
}

// Example 5: Conditional Notification with Huefy
class WeeklyDigestNotification extends Notification
{
    public function __construct(
        private array $digestData
    ) {}

    public function via($notifiable): array
    {
        // Use Huefy only for premium users, regular mail for others
        if ($notifiable->isPremium()) {
            return [HuefyChannel::class];
        }

        return ['mail'];
    }

    public function toHuefy($notifiable): HuefyMessage
    {
        return HuefyMessage::create('weekly-digest-premium')
            ->data([
                'subscriber_name' => $notifiable->name,
                'week_start' => now()->startOfWeek()->format('M j'),
                'week_end' => now()->endOfWeek()->format('M j, Y'),
                'articles' => $this->digestData['articles'],
                'stats' => $this->digestData['stats'],
                'premium_content' => $this->digestData['premium_content'],
                'unsubscribe_url' => route('unsubscribe', $notifiable->unsubscribe_token),
                'preferences_url' => route('preferences', $notifiable->id)
            ])
            ->provider('sendgrid'); // Premium users get SendGrid
    }

    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Your Weekly Digest')
            ->view('emails.weekly-digest', [
                'user' => $notifiable,
                'data' => $this->digestData
            ]);
    }
}

// Example Usage in Controllers/Services
class NotificationExamples
{
    public function sendWelcomeEmail($user, $activationToken)
    {
        $user->notify(new WelcomeNotification($activationToken));
    }

    public function sendPasswordReset($user, $token)
    {
        $user->notify(new PasswordResetNotification($token));
    }

    public function sendOrderConfirmation($order)
    {
        $order->customer->notify(new OrderConfirmationNotification($order));
    }

    public function sendBulkNotifications()
    {
        // Send to multiple users
        $users = \App\Models\User::whereNotNull('email_verified_at')->get();
        
        $digestData = [
            'articles' => $this->getWeeklyArticles(),
            'stats' => $this->getWeeklyStats(),
            'premium_content' => $this->getPremiumContent()
        ];

        \Illuminate\Support\Facades\Notification::send(
            $users, 
            new WeeklyDigestNotification($digestData)
        );
    }

    private function getWeeklyArticles(): array
    {
        return [
            ['title' => 'Laravel 11 Released', 'url' => 'https://example.com/laravel-11'],
            ['title' => 'PHP 8.3 Features', 'url' => 'https://example.com/php-83']
        ];
    }

    private function getWeeklyStats(): array
    {
        return [
            'total_users' => 15420,
            'new_users' => 342,
            'articles_published' => 12
        ];
    }

    private function getPremiumContent(): array
    {
        return [
            ['title' => 'Advanced Laravel Patterns', 'url' => 'https://premium.example.com/patterns'],
            ['title' => 'Performance Optimization', 'url' => 'https://premium.example.com/performance']
        ];
    }
}