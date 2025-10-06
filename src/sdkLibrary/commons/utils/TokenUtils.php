<?php

namespace Inter\Sdk\sdkLibrary\commons\utils;

use DateMalformedStringException;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Inter\Sdk\sdkLibrary\commons\exceptions\CertificateException;
use Inter\Sdk\sdkLibrary\commons\models\Config;
use Inter\Sdk\sdkLibrary\commons\models\Error;
use Inter\Sdk\sdkLibrary\commons\models\GetTokenResponse;
use Inter\Sdk\sdkLibrary\commons\structure\Constants;
use RuntimeException;

class TokenUtils
{
    private const ADDITIONAL_TIME = 60;

    public const TOKEN_CACHE_TTL = 60 * 60 * 0.5; // 30 minutes in seconds

    private static array $tokenMap = [];

    /**
     * Get a valid token, either from the map or by generating a new one.
     *
     * @param Config $config
     * @param string $scope
     * @return string
     * @throws CertificateException|DateMalformedStringException
     */
    public static function get(Config $config, string $scope): string
    {
        $getTokenResponse = self::getFromMap($config->getClientId(), $config->getClientSecret(), $scope);

        if ($getTokenResponse === null || !self::validate($getTokenResponse)) {
            $getTokenResponse = self::generateToken($config, $scope);
            self::addToMap($config->getClientId(), $config->getClientSecret(), $scope, $getTokenResponse);
        }

        return $getTokenResponse->getAccessToken();
    }

    /**
     * Validate if a token response is still valid.
     *
     * @param GetTokenResponse $getTokenResponse
     * @return bool
     * @throws DateMalformedStringException
     */
    public static function validate(GetTokenResponse $getTokenResponse): bool
    {
        if (!$getTokenResponse) {
            return false;
        }

        $createdAt = $getTokenResponse->getCreatedAt();
        $expiresIn = $getTokenResponse->getExpiresIn();

        if (!$createdAt instanceof DateTime || !is_int($expiresIn)) {
            return false;
        }

        $expirationDate = (clone $createdAt)->modify("+{$expiresIn} seconds");
        $now = new DateTime();

        return ($now->getTimestamp() + self::ADDITIONAL_TIME) <= $expirationDate->getTimestamp();
    }

    /**
     * Get a token response from the map.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $scope
     * @return GetTokenResponse|null
     */
    public static function getFromMap(string $clientId, string $clientSecret, string $scope): ?GetTokenResponse
    {
        $key = "{$clientId}:{$clientSecret}:{$scope}";
        return self::$tokenMap[$key] ?? null;
    }

    /**
     * Add a token response to the map.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $scope
     * @param GetTokenResponse $getTokenResponse
     */
    public static function addToMap(string $clientId, string $clientSecret, string $scope, GetTokenResponse $getTokenResponse): void
    {
        $key = "{$clientId}:{$clientSecret}:{$scope}";
        self::$tokenMap[$key] = $getTokenResponse;
    }

    /**
     * Generate a new access token.
     *
     * @param Config $config The configuration object
     * @param string $scope The scope for the token
     * @return GetTokenResponse|null The token response or null if unsuccessful
     * @throws CertificateException If there's an error obtaining the token
     */
    public static function generateToken(Config $config, string $scope): ?GetTokenResponse
    {
        try {
            $data = [
                "client_id" => $config->getClientId(),
                "client_secret" => $config->getClientSecret(),
                "grant_type" => "client_credentials",
                "scope" => $scope
            ];

            $client = new Client([
                'cert' => $config->getCrt(),
                'ssl_key' => $config->getKey()
            ]);

            $response = $client->post(UrlUtils::buildUrl($config, Constants::URL_TOKEN), [
                'form_params' => $data
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Failed to retrieve access token, status code: ' . $response->getStatusCode());
            }

            $responseData = json_decode((string) $response->getBody(), true);

            if (!isset($responseData['created_at'])) {
                $responseData['created_at'] = (new DateTime())->format(DATE_ISO8601);
            }

            return GetTokenResponse::builder()
                ->accessToken($responseData['access_token'] ?? null)
                ->tokenType($responseData['token_type'] ?? null)
                ->expiresIn($responseData['expires_in'] ?? null)
                ->scope($responseData['scope'] ?? null)
                ->createdAt(isset($responseData['created_at']) ? strtotime($responseData['created_at']) : null)
                ->build();

        } catch (GuzzleException $exception) {
            throw new CertificateException(
                "Erro ao obter Token",
                new Error("Erro ao obter Token", "Não foi possível obter token utilizando os dados fornecidos")
            );
        }
    }
}
