<?php

namespace Inter\Sdk\sdkLibrary;

use Exception;
use Inter\Sdk\sdkLibrary\banking\BankingSdk;
use Inter\Sdk\sdkLibrary\billing\BillingSdk;
use Inter\Sdk\sdkLibrary\commons\enums\EnvironmentEnum;
use Inter\Sdk\sdkLibrary\commons\exceptions\CertificateExpiredException;
use Inter\Sdk\sdkLibrary\commons\exceptions\CertificateNotFoundException;
use Inter\Sdk\sdkLibrary\commons\exceptions\SdkException;
use Inter\Sdk\sdkLibrary\commons\models\Config;
use Inter\Sdk\sdkLibrary\commons\structure\Constants;
use Inter\Sdk\sdkLibrary\commons\utils\SslUtils;
use Inter\Sdk\sdkLibrary\interfaces\TokenCacheInterface;
use Inter\Sdk\sdkLibrary\pix\PixSdk;
use RuntimeException;

class InterSdk
{
    public const VERSION = "inter-sdk-php v1.0.0";
    private array $warnings = [];
    private Config $config;
    private $bankingSdk;
    private $billingSdk;
    private $pixSdk;

    /**
     * SDK for accessing Inter's PJ APIs.
     *
     * @param string $environment Environment configuration.
     * @param string $clientId Application identifier.
     * @param string $clientSecret Application secret.
     * @param string $certificate Certificate file, e.g., certs/inter.pfx.
     * @param string $certificatePassword Certificate password.
     *
     * @throws Exception If an error occurs during initialization.
     */
    public function __construct(string $environment, string $clientId, string $clientSecret, string $certificate, string $certificatePassword, ?TokenCacheInterface $tokenCache = null)
    {
        $this->config = new Config(
            EnvironmentEnum::fromLabel($environment),
            $clientId,
            $clientSecret,
            $certificate,
            $certificatePassword,
            '',
            '',
            $tokenCache
        );
        try {
            $keyAndCertificate = SslUtils::convertPfxToPem($this->config->getCertificate(), $this->config->getPassword());

            $privateKey = $keyAndCertificate['private_key'];
            $cert = $keyAndCertificate['certificate'];

            $expireSoon = SslUtils::isCertificateExpiringSoon($cert, Constants::DAYS_TO_EXPIRE);
        } catch (CertificateExpiredException|CertificateNotFoundException $e) {
            $message = $this->formatErrorMessage($e);
            throw new RuntimeException($message, 0, $e);
        }

        if ($expireSoon['expiring_soon']) {
            $this->warnings[] = "Certificate nearing expiration. Less than " . Constants::DAYS_TO_EXPIRE . " days left. Expires on " . $expireSoon['days_until_expiration'] . ".";
        }

        $dataPaths = SslUtils::getCertKeyName($privateKey, $cert);

        $this->config = new Config(
            EnvironmentEnum::fromLabel($environment),
            $clientId,
            $clientSecret,
            $certificate,
            $certificatePassword,
            $dataPaths['certificate_path'],
            $dataPaths['private_key_path'],
            $tokenCache
        );

        // Create logs directory if it doesn't exist
        if (!file_exists("logs")) {
            mkdir("logs", 0755, true);
        }

        $tomorrow = "logs/inter-sdk-" . (new \DateTime('+1 day'))->format('D') . ".log";
        if (file_exists($tomorrow)) {
            unlink($tomorrow);
        }
    }

    private function formatErrorMessage(\Exception $e): string
    {
        return "Error: " . $e->getMessage();
    }

     /**
      * Sdk for API banking.
      *
      * @return BankingSdk The banking SDK instance.
      */
     public function banking(): BankingSdk
     {
         if ($this->bankingSdk === null) {
             $this->bankingSdk = new BankingSdk($this->config);
         }
         return $this->bankingSdk;
     }

     /**
      * Sdk for API billing.
      *
      * @return BillingSdk The billing SDK instance.
      */
     public function billing(): BillingSdk
     {
         if ($this->billingSdk === null) {
             $this->billingSdk = new BillingSdk($this->config);
         }
         return $this->billingSdk;
     }

     /**
      * Sdk for API pix.
      *
      * @return PixSdk The pix SDK instance.
      */
     public function pix(): PixSdk
     {
         if ($this->pixSdk === null) {
             $this->pixSdk = new PixSdk($this->config);
         }
         return $this->pixSdk;
     }

    /**
     * Returns the list of warnings from the last operation.
     *
     * @return array List of warnings, may be empty.
     */
    public function warningList(): array
    {
        return $this->warnings;
    }

    /**
     * Configures the debug mode. In debug mode, the request and response data will be logged.
     *
     * @param bool $debug Indicates if debug mode should be enabled.
     */
    public function setDebug(bool $debug): void
    {
        $this->config->setDebug($debug);
    }

    /**
     * Indicates whether it will perform automatic rate limit control.
     *
     * @param bool $control Indicates if the SDK will perform automatic control - default is true.
     */
    public function setRateLimitControl(bool $control): void
    {
        $this->config->setRateLimitControl($control);
    }

    /**
     * Selects the current account. Necessary only if the application is configured with multiple accounts.
     *
     * @param string $account Current account number.
     */
    public function setAccount(string $account): void
    {
        $this->config->setAccount($account);
    }

    /**
     * Returns the selected checking account.
     *
     * @return string Selected checking account.
     */
    public function getAccount(): string
    {
        return $this->config->getAccount();
    }

    public function getConfig() : Config
    {
        return $this->config;
    }
}
