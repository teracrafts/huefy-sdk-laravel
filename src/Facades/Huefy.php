<?php

namespace TeraCrafts\HuefyLaravel\Facades;

use Illuminate\Support\Facades\Facade;
use TeraCrafts\HuefyLaravel\HuefyClient;

/**
 * @method static array sendEmail(string $templateKey, array $data, string $recipient, ?string $provider = null)
 * @method static array sendBulkEmails(array $emails)
 * @method static array healthCheck()
 * @method static bool validateTemplate(string $templateKey, array $testData)
 * @method static array getProviders()
 *
 * @see HuefyClient
 */
class Huefy extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'huefy';
    }
}