<?php

namespace TeraCrafts\HuefyLaravel\Notifications;

class HuefyMessage
{
    private string $templateKey;
    private array $data = [];
    private ?string $recipient = null;
    private ?string $provider = null;

    /**
     * Create a new Huefy message instance.
     */
    public function __construct(string $templateKey = '')
    {
        $this->templateKey = $templateKey;
    }

    /**
     * Create a new message instance.
     */
    public static function create(string $templateKey = ''): self
    {
        return new static($templateKey);
    }

    /**
     * Set the template key for the message.
     */
    public function template(string $templateKey): self
    {
        $this->templateKey = $templateKey;
        return $this;
    }

    /**
     * Set the template data for the message.
     */
    public function data(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Add a single data item to the template data.
     */
    public function with(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Set the recipient email address.
     */
    public function to(string $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * Set the email provider to use.
     */
    public function provider(string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * Get the template key.
     */
    public function getTemplateKey(): string
    {
        return $this->templateKey ?: config('huefy.notifications.default_template_key', '');
    }

    /**
     * Get the template data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the recipient email address.
     */
    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    /**
     * Get the email provider.
     */
    public function getProvider(): ?string
    {
        return $this->provider ?: config('huefy.default_provider');
    }

    /**
     * Convert the message to an array.
     */
    public function toArray(): array
    {
        return [
            'template_key' => $this->getTemplateKey(),
            'data' => $this->getData(),
            'recipient' => $this->getRecipient(),
            'provider' => $this->getProvider(),
        ];
    }
}