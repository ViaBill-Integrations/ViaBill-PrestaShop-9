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

namespace ViaBill\Service\Api;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use ViaBill\Adapter\Tools;
use ViaBill\Factory\HttpClientFactory;
use ViaBill\Object\Api\ApiResponse;
use ViaBill\Object\Api\ApiResponseError;

/**
 * Class ApiRequest
 *
 *   - 4xx responses are decoded for structured `errors` payloads.
 *   - 5xx responses and network failures produce a generic "service
 *     unavailable" message (the body is intentionally not parsed for 5xx,
 *     matching the original Guzzle RequestException branch).
 *   - The returned ApiResponse carries (statusCode, body, errors, effectiveUrl).
 */
class ApiRequest
{
    /**
     * @var \ViaBill
     */
    private $module;

    /**
     * @var HttpClientFactory
     */
    private $clientFactory;

    /**
     * @var Tools
     */
    private $tools;

    public function __construct(\ViaBill $module, HttpClientFactory $clientFactory, Tools $tools)
    {
        $this->module = $module;
        $this->clientFactory = $clientFactory;
        $this->tools = $tools;
    }

    /**
     * API Request POST.
     *
     * @param string $url
     * @param array  $params  Symfony HttpClient options: 'json', 'body',
     *                        'headers', 'query', 'auth_basic', 'auth_bearer'...
     *
     * @return ApiResponse
     */
    public function post($url, $params = [])
    {
        return $this->send('POST', $url, $params, true);
    }

    /**
     * API Request GET.
     *
     * @param string $url
     * @param array  $options
     *
     * @return ApiResponse
     */
    public function get($url, $options = [])
    {
        return $this->send('GET', $url, $options, false);
    }

    /**
     * Shared request pipeline. Symfony HttpClient defers the actual HTTP call
     * until the response is read (getStatusCode / getContent), so all reads
     * happen inside the try block.
     *
     * @param string $method
     * @param string $url
     * @param array  $options
     * @param bool   $trackEffectiveUrl  Only POST records it (matching legacy behavior).
     *
     * @return ApiResponse
     */
    private function send($method, $url, array $options, $trackEffectiveUrl)
    {
        $body = '';
        $errors = [];
        $statusCode = 0;
        $effectiveUrl = $url;

        // Strip any Guzzle-only options a legacy caller might still pass.
        // Symfony HttpClient throws InvalidArgumentException on unknown keys.
        unset($options['on_stats'], $options['allow_redirects'], $options['debug']);

        try {
            /** @var ResponseInterface $response */
            $response = $this->clientFactory->getClient()->request($method, $url, $options);

            // Touching getStatusCode() materializes the response. Neither this
            // nor getContent(false) throw on non-2xx, which lets us read error
            // bodies without try/catch gymnastics.
            $statusCode = $response->getStatusCode();
            $body = $response->getContent(false);

            if ($trackEffectiveUrl) {
                $info = $response->getInfo('url');
                if (is_string($info) && $info !== '') {
                    $effectiveUrl = $info;
                }
            }

            if ($statusCode >= 400 && $statusCode < 500) {
                // Was: catch (ClientException) — parse structured errors from the body.
                $errors = $this->getResponseErrors(
                    $body,
                    sprintf('Client error: HTTP %d', $statusCode)
                );
            } elseif ($statusCode >= 500) {
                // Was: catch (RequestException) for 5xx — generic message, body discarded.
                $errors = $this->getResponseErrors('', $this->serviceUnavailableMessage());
            }
        } catch (TransportExceptionInterface $transportException) {
            // Network-level: DNS failure, connection refused, TLS, timeout.
            // Was: catch (RequestException) without response.
            $statusCode = (int) $transportException->getCode();
            $errors = $this->getResponseErrors('', $this->serviceUnavailableMessage());
        } catch (\Exception $exception) {
            $statusCode = (int) $exception->getCode();
            $errors = $this->getResponseErrors('', $exception->getMessage());
        }

        return $trackEffectiveUrl
            ? new ApiResponse($statusCode, $body, $errors, $effectiveUrl)
            : new ApiResponse($statusCode, $body, $errors);
    }

    /**
     * Gets API Request Response Errors.
     *
     * @param string $body
     * @param string $exceptionMessage
     *
     * @return array
     */
    private function getResponseErrors($body, $exceptionMessage)
    {
        $normalizedBody = json_decode($body, true);

        if (empty($normalizedBody['errors']) || !isset($normalizedBody['errors'])) {
            return [new ApiResponseError('', $exceptionMessage)];
        }

        $errors = $normalizedBody['errors'];
        $result = [];

        foreach ($errors as $error) {
            $result[] = new ApiResponseError(
                (string) $error['field'],
                (string) $error['error']
            );
        }

        return $result;
    }

    /**
     * @return string
     */
    private function serviceUnavailableMessage()
    {
        return $this->module->l(
            'ViaBill service is currently unavailable. Please refresh the page in a moment or contact ViaBill support at merchants@viabill.com.'
        );
    }
}