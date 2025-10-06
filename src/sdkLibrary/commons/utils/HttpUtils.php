<?php

namespace Inter\Sdk\sdkLibrary\commons\utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Inter\Sdk\sdkLibrary\commons\exceptions\CertificateException;
use Inter\Sdk\sdkLibrary\commons\exceptions\ClientException;
use Inter\Sdk\sdkLibrary\commons\exceptions\SdkException;
use Inter\Sdk\sdkLibrary\commons\exceptions\ServerException;
use Inter\Sdk\sdkLibrary\commons\models\Config;
use Inter\Sdk\sdkLibrary\commons\models\Error;
use Psr\Http\Message\ResponseInterface;

class HttpUtils
{
    public const SUCCESSFUL = 200;
    public const REDIRECTION = 300;
    public const CLIENT_ERROR_BASE = 400;
    public const SERVER_ERROR_BASE = 500;
    public const TOO_MANY_REQUESTS = 429;
    public const NO_CONTENT = [204];

    /**
     * Makes an HTTP GET request.
     *
     * @param Config $config The configuration object.
     * @param string $url The URL to send the request to.
     * @param string $scope The scope of the request.
     * @param string $message A message to log if an error occurs.
     * @return string The response body.
     * @throws SdkException
     */
    public static function callGet(Config $config, string $url, string $scope, string $message): string
    {
        return self::call($config, "GET", $url, $scope, $message, "");
    }

    /**
     * Makes an HTTP PUT request.
     *
     * @param Config $config The configuration object.
     * @param string $url The URL to send the request to.
     * @param string $scope The scope of the request.
     * @param string $message A message to log if an error occurs.
     * @param string $jsonData The JSON data to send in the request body.
     * @return string The response body.
     * @throws SdkException
     */
    public static function callPut(Config $config, string $url, string $scope, string $message, string $jsonData): string
    {
        return self::call($config, "PUT", $url, $scope, $message, $jsonData);
    }

    /**
     * Makes an HTTP PATCH request.
     *
     * @param Config $config The configuration object.
     * @param string $url The URL to send the request to.
     * @param string $scope The scope of the request.
     * @param string $message A message to log if an error occurs.
     * @param string $jsonData The JSON data to send in the request body.
     * @return string The response body.
     * @throws SdkException
     */
    public static function callPatch(Config $config, string $url, string $scope, string $message, string $jsonData): string
    {
        return self::call($config, "PATCH", $url, $scope, $message, $jsonData);
    }

    /**
     * Makes an HTTP POST request.
     *
     * @param Config $config The configuration object.
     * @param string $url The URL to send the request to.
     * @param string $scope The scope of the request.
     * @param string $message A message to log if an error occurs.
     * @param string $jsonData The JSON data to send in the request body.
     * @return string The response body.
     * @throws SdkException
     */
    public static function callPost(Config $config, string $url, string $scope, string $message, string $jsonData): string
    {
        return self::call($config, "POST", $url, $scope, $message, $jsonData);
    }

    /**
     * Makes an HTTP DELETE request.
     *
     * @param Config $config The configuration object.
     * @param string $url The URL to send the request to.
     * @param string $scope The scope of the request.
     * @param string $message A message to log if an error occurs.
     * @return string The response body.
     * @throws SdkException
     */
    public static function callDelete(Config $config, string $url, string $scope, string $message): string
    {
        // Aqui você pode usar um logger para registrar a informação
        // Exemplo: $this->logger->info("http DELETE {$url}");
        return self::call($config, "DELETE", $url, $scope, $message, "");
    }

    /**
     * Make an HTTP request.
     *
     * @param Config $config
     * @param string $method
     * @param string $url
     * @param string $scope
     * @param string $message
     * @param string $jsonData
     * @return string
     * @throws SdkException
     */
    public static function call(Config $config, string $method, string $url, string $scope, string $message, string $jsonData): string
    {
        try {
            $accessToken = null;

            if ($config->tokenCache !== null) {
                $accessToken = $config->tokenCache->get($config->getClientId() . $scope);
            }

            if (!$accessToken) {
                $accessToken = TokenUtils::get($config, $scope);
                if ($config->tokenCache !== null && $accessToken !== null) {
                    $config->tokenCache->set($config->getClientId() . $scope, $accessToken, TokenUtils::TOKEN_CACHE_TTL);
                }
            }

            $headers = [
                'Authorization' => "Bearer {$accessToken}",
                'x-inter-sdk' => 'php',
                'x-inter-sdk-version' => '1.0.2',
                'Content-Type' => 'application/json'
            ];

            if ($config->getAccount() !== null) {
                $headers['x-conta-corrente'] = $config->getAccount();
            }

            $client = new Client([
                'cert' => $config->getCrt(),
                'ssl_key' => $config->getKey()
            ]);

            $options = [
                'headers' => $headers,
            ];

            if (!empty($jsonData) && in_array($method, ['PUT', 'POST', 'PATCH'])) {
                $options['body'] = $jsonData;
            }

            $response = $client->request($method, $url, $options);

            $retry = self::handleResponse($url, $response, $message, $config->isRateLimitControl());

            if ($retry) {
                sleep(60);
                return self::call($config, $method, $url, $scope, $message, $jsonData);
            }

            if ($config->isDebug() && $response->getBody()) {
                error_log($response->getBody());
            }

            if (in_array($response->getStatusCode(), [204, 304])) {
                return '';
            }

            return (string) $response->getBody();

        } catch (GuzzleException $e) {
            if ($config->tokenCache !== null) {
                $config->tokenCache->delete($config->getClientId() . $scope);
            }
            throw new SdkException(
                "Error executing request: " . $e->getMessage(),
                new Error("Request error", $e->getMessage(), null)
            );
        }
    }

    /**
     * Handle the HTTP response and throw exceptions if necessary.
     *
     * @param string $url The URL of the request.
     * @param ResponseInterface $response The HTTP response object.
     * @param string $message The error message to use in case of an exception.
     * @param bool $rateLimitControl Whether to handle rate limiting.
     * @return bool True if the request should be retried due to rate limiting, false otherwise.
     * @throws ServerException|ClientException
     */
    public static function handleResponse(string $url, ResponseInterface $response, string $message, bool $rateLimitControl): bool
    {
        $statusCode = $response->getStatusCode();
        error_log("http status=$statusCode $url");

        if ($statusCode >= self::SUCCESSFUL && $statusCode < self::REDIRECTION) {
            return false;
        }

        if ($statusCode >= self::SERVER_ERROR_BASE) {
            $error = self::convertJsonToError($response->getBody()->getContents());
            throw new ServerException($message, $error);
        }

        if ($statusCode >= self::CLIENT_ERROR_BASE) {
            if ($statusCode === self::TOO_MANY_REQUESTS && $rateLimitControl) {
                return true;
            }

            $jsonBody = $response->getBody()->getContents();
            if (empty($jsonBody)) {
                $detail = $response->getReasonPhrase() ?: "";
                $error = new Error($statusCode, $detail, "");
            } else {
                $error = self::convertJsonToError($jsonBody);
            }

            throw new ClientException($message, $error);
        }

        return false;
    }

    /**
     * Convert JSON string to Error object.
     *
     * @param string $jsonString
     * @return Error
     */
    private static function convertJsonToError(string $jsonString): Error
    {
        $data = json_decode($jsonString, true);
        return new Error(
            $data['title'] ?? '',
            $data['detail'] ?? '',
            $data['timestamp'] ?? ''
        );
    }
}
