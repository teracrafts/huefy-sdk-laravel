<?php

namespace TeraCrafts\HuefyLaravel\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use TeraCrafts\HuefyLaravel\Exceptions\HuefyException;
use TeraCrafts\HuefyLaravel\HuefyClient;

class HuefyChannel
{
    private HuefyClient $client;

    public function __construct(HuefyClient $client)
    {
        $this->client = $client;
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): void
    {
        // Get the notification data
        $message = $notification->toHuefy($notifiable);

        if (!$message instanceof HuefyMessage) {
            throw new HuefyException(
                'Notification must return a HuefyMessage instance from toHuefy() method.'
            );
        }

        // Get recipient email
        $recipient = $this->getRecipientEmail($notifiable, $message);
        if (!$recipient) {
            throw new HuefyException('Could not determine recipient email address.');
        }

        // Prepare template data
        $templateData = $message->getData();
        
        if (config('huefy.notifications.include_notification_data', true)) {
            $templateData = array_merge($templateData, [
                'notification_id' => $notification->id,
                'notification_type' => get_class($notification),
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->getKey(),
            ]);
        }

        try {
            $response = $this->client->sendEmail(
                $message->getTemplateKey(),
                $templateData,
                $recipient,
                $message->getProvider()
            );

            if (config('huefy.logging.log_successful_sends', false)) {
                Log::channel(config('huefy.logging.channel'))->info('Huefy notification sent successfully', [
                    'notification_id' => $notification->id,
                    'notification_type' => get_class($notification),
                    'template_key' => $message->getTemplateKey(),
                    'recipient' => $recipient,
                    'message_id' => $response['message_id'] ?? null,
                    'provider' => $response['provider'] ?? null,
                ]);
            }

        } catch (HuefyException $e) {
            if (config('huefy.logging.log_failed_sends', true)) {
                Log::channel(config('huefy.logging.channel'))->error('Huefy notification send failed', [
                    'notification_id' => $notification->id,
                    'notification_type' => get_class($notification),
                    'template_key' => $message->getTemplateKey(),
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Get the recipient email address
     */
    private function getRecipientEmail($notifiable, HuefyMessage $message): ?string
    {
        // Check if message has explicit recipient
        if ($message->getRecipient()) {
            return $message->getRecipient();
        }

        // Try to get email from notifiable
        if (method_exists($notifiable, 'routeNotificationForHuefy')) {
            return $notifiable->routeNotificationForHuefy();
        }

        if (method_exists($notifiable, 'routeNotificationForMail')) {
            $route = $notifiable->routeNotificationForMail();
            if (is_string($route)) {
                return $route;
            }
            if (is_array($route) && isset($route['email'])) {
                return $route['email'];
            }
        }

        // Try common email attributes
        if (isset($notifiable->email)) {
            return $notifiable->email;
        }

        if (isset($notifiable->email_address)) {
            return $notifiable->email_address;
        }

        return null;
    }
}