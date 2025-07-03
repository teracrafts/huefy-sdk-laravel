<?php

namespace TeraCrafts\HuefyLaravel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use TeraCrafts\HuefyLaravel\Exceptions\HuefyException;
use TeraCrafts\HuefyLaravel\Exceptions\TemplateNotFoundException;
use TeraCrafts\HuefyLaravel\Exceptions\ValidationException;

class HuefyClient
{
    private Client $httpClient;
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private int $retryAttempts;

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.huefy.com/api/v1/sdk',
        int $timeout = 30,
        int $retryAttempts = 3
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->retryAttempts = $retryAttempts;

        $this->httpClient = new Client([
            RequestOptions::TIMEOUT => $this->timeout,
            RequestOptions::HEADERS => [
                'X-API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'Huefy-Laravel-SDK/1.0',
            ],
        ]);
    }

    /**
     * Send a single email using a template
     */
    public function sendEmail(
        string $templateKey,
        array $data,
        string $recipient,
        ?string $provider = null
    ): array {
        $payload = [
            'template_key' => $templateKey,
            'data' => $data,
            'recipient' => $recipient,
        ];

        if ($provider) {
            $payload['provider'] = $provider;
        }

        return $this->makeRequest('POST', '/emails/send', $payload);
    }

    /**
     * Send multiple emails in bulk
     */
    public function sendBulkEmails(array $emails): array
    {
        return $this->makeRequest('POST', '/emails/bulk', ['emails' => $emails]);
    }

    /**
     * Check API health
     */
    public function healthCheck(): array
    {
        return $this->makeRequest('GET', '/health');
    }

    /**
     * Validate a template with test data
     */
    public function validateTemplate(string $templateKey, array $testData): bool
    {
        try {
            $response = $this->makeRequest('POST', '/templates/validate', [
                'template_key' => $templateKey,
                'test_data' => $testData,
            ]);

            return $response['valid'] ?? false;
        } catch (HuefyException $e) {
            return false;
        }
    }

    /**
     * Get available email providers
     */
    public function getProviders(): array
    {
        return $this->makeRequest('GET', '/providers');
    }

    /**
     * Make HTTP request with retry logic
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retryAttempts) {
            try {
                $options = [];
                if (!empty($data)) {
                    $options[RequestOptions::JSON] = $data;
                }

                $response = $this->httpClient->request($method, $url, $options);
                $body = $response->getBody()->getContents();
                $decoded = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new HuefyException('Invalid JSON response: ' . json_last_error_msg());
                }

                return $decoded;

            } catch (GuzzleException $e) {
                $lastException = $e;
                $attempt++;

                // Don't retry client errors (4xx)
                if ($e->hasResponse() && $e->getResponse()->getStatusCode() < 500) {
                    $this->handleClientError($e);
                }

                if ($attempt < $this->retryAttempts) {
                    $delay = min(1000 * (2 ** $attempt), 10000); // Exponential backoff, max 10s
                    usleep($delay * 1000);
                    Log::warning("Huefy API request failed, retrying in {$delay}ms", [
                        'attempt' => $attempt,
                        'endpoint' => $endpoint,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // All retries failed
        if ($lastException) {
            Log::error('Huefy API request failed after all retries', [
                'endpoint' => $endpoint,
                'attempts' => $this->retryAttempts,
                'error' => $lastException->getMessage(),
            ]);

            if ($lastException->hasResponse()) {
                $this->handleClientError($lastException);
            }

            throw new HuefyException(
                'Request failed after ' . $this->retryAttempts . ' attempts: ' . $lastException->getMessage(),
                0,
                $lastException
            );
        }

        throw new HuefyException('Unknown error occurred');
    }

    /**
     * Handle client errors (4xx responses)
     */
    private function handleClientError(GuzzleException $e): void
    {
        if (!$e->hasResponse()) {
            throw new HuefyException($e->getMessage(), 0, $e);
        }

        $response = $e->getResponse();
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $decoded = json_decode($body, true);

        $message = $decoded['message'] ?? $e->getMessage();
        $code = $decoded['code'] ?? null;

        switch ($statusCode) {
            case 400:
                if (str_contains($message, 'template') && str_contains($message, 'not found')) {
                    throw new TemplateNotFoundException($message, $statusCode);
                }
                throw new ValidationException($message, $statusCode);

            case 401:
                throw new HuefyException('Invalid API key', $statusCode);

            case 403:
                throw new HuefyException('Access forbidden', $statusCode);

            case 404:
                if (str_contains($message, 'template')) {
                    throw new TemplateNotFoundException($message, $statusCode);
                }
                throw new HuefyException('Resource not found', $statusCode);

            case 422:
                throw new ValidationException($message, $statusCode, $decoded['errors'] ?? []);

            case 429:
                $retryAfter = $response->getHeader('Retry-After')[0] ?? 60;
                throw new HuefyException("Rate limit exceeded. Retry after {$retryAfter} seconds", $statusCode);

            default:
                throw new HuefyException($message, $statusCode);
        }
    }
}