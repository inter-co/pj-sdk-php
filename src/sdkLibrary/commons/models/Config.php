<?php

namespace Inter\Sdk\sdkLibrary\commons\models;

use Inter\Sdk\sdkLibrary\commons\enums\EnvironmentEnum;
use Inter\Sdk\sdkLibrary\interfaces\TokenCacheInterface;

/**
 * This class represents the necessary configurations
 * for integration with the system. This class contains sensitive
 * information and crucial operating parameters for the client's
 * functionality.
 */
class Config
{
    /**
     * The environment in which the client is operating.
     */
    private EnvironmentEnum $environment;

    /**
     * The client ID for authentication with the system.
     */
    private string $clientId;

    /**
     * The client secret for authentication with the system.
     */
    private string $clientSecret;

    /**
     * The certificate used for secure communication with the system.
     */
    private string $certificate;

    /**
     * The password for accessing the client's certificate.
     */
    private string $password;

    /**
     * Indicates whether debug mode is enabled.
     */
    private bool $debug = false;

    /**
     * The account identifier associated with the client's integration.
     */
    private ?string $account = null;

    /**
     * Control for rate limit enforcement.
     */
    private bool $rateLimitControl = false;
    /**
     * The CRT file path for SSL communication.
     */
    private string $crt;

    /**
     * The KEY file path for SSL communication.
     */
    private string $key;

    public function __construct(
        EnvironmentEnum $environment,
        string $clientId,
        string $clientSecret,
        string $certificate,
        string $password,
        string $crt,
        string $key,
        public ?TokenCacheInterface $tokenCache = null
    ) {
        $this->environment = $environment;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->certificate = $certificate;
        $this->password = $password;
        $this->crt = $crt;
        $this->key = $key;
    }

    // Getters
    public function getEnvironment(): EnvironmentEnum
    {
        return $this->environment;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getCertificate(): string
    {
        return $this->certificate;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getCrt(): string
    {
        return $this->crt;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getAccount(): ?string
    {
        return $this->account;
    }

    public function isRateLimitControl(): bool
    {
        return $this->rateLimitControl;
    }

    // Setters
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    public function setAccount(?string $account): void
    {
        $this->account = $account;
    }

    public function setRateLimitControl(bool $rateLimitControl): void
    {
        $this->rateLimitControl = $rateLimitControl;
    }

    public function setCrt(?string $crt): void
    {
        $this->crt = $crt;
    }

    public function setKey(bool $key): void
    {
        $this->key = $key;
    }

}
