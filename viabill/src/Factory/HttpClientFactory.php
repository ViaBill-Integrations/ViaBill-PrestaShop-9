<?php
/**
* NOTICE OF LICENSE
*
* @author    Written for or by ViaBill
* @copyright Copyright (c) Viabill
* @license   Addons PrestaShop license limitation
*
* @see       /LICENSE
*/

namespace ViaBill\Factory;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use ViaBill\Config\Config;

class HttpClientFactory
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getClient(): HttpClientInterface
    {
        return HttpClient::createForBaseUri(
            $this->config->getBaseUrl(),
            [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                // Idle timeout, plus a hard limit for the whole request: the
                // ViaBill API is called during checkout and must never hang it.
                'timeout' => 30,
                'max_duration' => 60,
                // Always verify the ViaBill TLS certificate.
                'verify_peer' => true,
                'verify_host' => true,
            ]
        );
    }
}
