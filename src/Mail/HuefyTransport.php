<?php

namespace TeraCrafts\HuefyLaravel\Mail;

use Illuminate\Mail\Transport\Transport;
use Illuminate\Support\Facades\Log;
use Swift_Mime_SimpleMessage;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use TeraCrafts\HuefyLaravel\Exceptions\HuefyException;
use TeraCrafts\HuefyLaravel\HuefyClient;

class HuefyTransport extends Transport implements TransportInterface
{
    private HuefyClient $client;
    private ?string $defaultTemplateKey;
    private ?string $defaultProvider;

    public function __construct(
        HuefyClient $client,
        ?string $defaultTemplateKey = null,
        ?string $defaultProvider = null
    ) {
        parent::__construct();
        $this->client = $client;
        $this->defaultTemplateKey = $defaultTemplateKey;
        $this->defaultProvider = $defaultProvider;
    }

    /**
     * Send a message (Symfony Mailer interface)
     */
    public function send(Email $message, ?SentMessage $envelope = null): ?SentMessage
    {
        $templateKey = $this->extractTemplateKey($message);
        $templateData = $this->extractTemplateData($message);
        $recipient = $this->extractRecipient($message);
        $provider = $this->extractProvider($message);

        if (!$templateKey) {
            throw new HuefyException('No template key specified. Use X-Template-Key header or configure a default.');
        }

        if (!$recipient) {
            throw new HuefyException('No recipient specified.');
        }

        try {
            $response = $this->client->sendEmail($templateKey, $templateData, $recipient, $provider);

            if (config('huefy.logging.log_successful_sends', false)) {
                Log::channel(config('huefy.logging.channel'))->info('Huefy email sent successfully', [
                    'template_key' => $templateKey,
                    'recipient' => $recipient,
                    'message_id' => $response['message_id'] ?? null,
                    'provider' => $response['provider'] ?? null,
                ]);
            }

            return new SentMessage($message, $envelope ?? SentMessage::fromMessage($message));

        } catch (HuefyException $e) {
            if (config('huefy.logging.log_failed_sends', true)) {
                Log::channel(config('huefy.logging.channel'))->error('Huefy email send failed', [
                    'template_key' => $templateKey,
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Legacy send method for older Laravel versions
     */
    public function sendLegacy(Swift_Mime_SimpleMessage $message, &$failedRecipients = null): int
    {
        $templateKey = $this->extractTemplateKeyLegacy($message);
        $templateData = $this->extractTemplateDataLegacy($message);
        $recipients = array_keys($message->getTo());
        $provider = $this->extractProviderLegacy($message);

        if (!$templateKey) {
            throw new HuefyException('No template key specified. Use X-Template-Key header or configure a default.');
        }

        if (empty($recipients)) {
            throw new HuefyException('No recipients specified.');
        }

        $sentCount = 0;
        $failedRecipients = [];

        foreach ($recipients as $recipient) {
            try {
                $response = $this->client->sendEmail($templateKey, $templateData, $recipient, $provider);
                $sentCount++;

                if (config('huefy.logging.log_successful_sends', false)) {
                    Log::channel(config('huefy.logging.channel'))->info('Huefy email sent successfully', [
                        'template_key' => $templateKey,
                        'recipient' => $recipient,
                        'message_id' => $response['message_id'] ?? null,
                        'provider' => $response['provider'] ?? null,
                    ]);
                }

            } catch (HuefyException $e) {
                $failedRecipients[] = $recipient;

                if (config('huefy.logging.log_failed_sends', true)) {
                    Log::channel(config('huefy.logging.channel'))->error('Huefy email send failed', [
                        'template_key' => $templateKey,
                        'recipient' => $recipient,
                        'error' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ]);
                }
            }
        }

        return $sentCount;
    }

    /**
     * Extract template key from message headers
     */
    private function extractTemplateKey(Email $message): ?string
    {
        $headers = $message->getHeaders();
        
        if ($headers->has('X-Template-Key')) {
            return $headers->get('X-Template-Key')->getBody();
        }

        return $this->defaultTemplateKey;
    }

    /**
     * Extract template key from legacy message
     */
    private function extractTemplateKeyLegacy(Swift_Mime_SimpleMessage $message): ?string
    {
        $headers = $message->getHeaders();
        
        if ($headers->has('X-Template-Key')) {
            return $headers->get('X-Template-Key')->getValue();
        }

        return $this->defaultTemplateKey;
    }

    /**
     * Extract template data from message
     */
    private function extractTemplateData(Email $message): array
    {
        $headers = $message->getHeaders();
        
        if ($headers->has('X-Template-Data')) {
            $data = json_decode($headers->get('X-Template-Data')->getBody(), true);
            return $data ?: [];
        }

        // Try to extract from message body or other sources
        return [];
    }

    /**
     * Extract template data from legacy message
     */
    private function extractTemplateDataLegacy(Swift_Mime_SimpleMessage $message): array
    {
        $headers = $message->getHeaders();
        
        if ($headers->has('X-Template-Data')) {
            $data = json_decode($headers->get('X-Template-Data')->getValue(), true);
            return $data ?: [];
        }

        return [];
    }

    /**
     * Extract recipient email address
     */
    private function extractRecipient(Email $message): ?string
    {
        $to = $message->getTo();
        if (empty($to)) {
            return null;
        }

        return $to[0]->getAddress();
    }

    /**
     * Extract provider from message headers
     */
    private function extractProvider(Email $message): ?string
    {
        $headers = $message->getHeaders();
        
        if ($headers->has('X-Email-Provider')) {
            return $headers->get('X-Email-Provider')->getBody();
        }

        return $this->defaultProvider ?: config('huefy.default_provider');
    }

    /**
     * Extract provider from legacy message
     */
    private function extractProviderLegacy(Swift_Mime_SimpleMessage $message): ?string
    {
        $headers = $message->getHeaders();
        
        if ($headers->has('X-Email-Provider')) {
            return $headers->get('X-Email-Provider')->getValue();
        }

        return $this->defaultProvider ?: config('huefy.default_provider');
    }

    /**
     * Get string representation of the transport
     */
    public function __toString(): string
    {
        return 'huefy';
    }
}