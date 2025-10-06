<?php

namespace Inter\Sdk\sdkLibrary\banking\models;

use Inter\Sdk\sdkLibrary\banking\enums\AccountType;
use JsonException;

/**
 * The BankDetails class contains information about bank account details,
 * including account type, agency, CPF/CNPJ, and associated financial institution.
 */
class BankDetails extends Recipient
{
    /**
     * The type of the bank details.
     *
     * @var string
     */
    private string $type = "DADOS_BANCARIOS";
    /**
     * The bank account number.
     *
     * @var string|null
     */
    private ?string $account;
    /**
     * The type of bank account (savings, checking, etc.).
     *
     * @var AccountType|null
     */
    private ?AccountType $accountType;
    /**
     * The CPF or CNPJ associated with the account.
     *
     * @var string|null
     */
    private ?string $cpfCnpj;
    /**
     * The bank agency number.
     *
     * @var string|null
     */
    private ?string $agency;
    /**
     * The name of the account holder.
     *
     * @var string|null
     */
    private ?string $name;
    /**
     * The financial institution associated with the account.
     *
     * @var FinancialInstitution|null
     */
    private ?FinancialInstitution $financialInstitution;
    /**
     * Constructs a new BankDetails with specified values.
     *
     * @param string|null $account
     * @param AccountType|null $accountType
     * @param string|null $cpfCnpj
     * @param string|null $agency
     * @param string|null $name
     * @param FinancialInstitution|null $financialInstitution
     */
    public function __construct(
        ?string $account = null,
        ?AccountType $accountType = null,
        ?string $cpfCnpj = null,
        ?string $agency = null,
        ?string $name = null,
        ?FinancialInstitution $financialInstitution = null
    ) {
        $this->account = $account;
        $this->accountType = $accountType;
        $this->cpfCnpj = $cpfCnpj;
        $this->agency = $agency;
        $this->name = $name;
        $this->financialInstitution = $financialInstitution;
    }
    /**
     * Creates a BankDetails instance from a JSON string.
     *
     * @param mixed $json The JSON string containing the bank details data.
     * @return BankDetails An instance of BankDetails.
     */
    public static function fromJson(mixed $json): self
    {
        return new self(
            $json['contaCorrente'] ?? null,
            isset($json['tipoConta']) ? AccountType::from($json['tipoConta']) : null,
            $json['cpfCnpj'] ?? null,
            $json['agencia'] ?? null,
            $json['nome'] ?? null,
            isset($json['instituicaoFinanceira']) ? FinancialInstitution::fromJson($json['instituicaoFinanceira']) : null
        );
    }
    /**
     * Convert the BankDetails instance to a JSON string.
     *
     * @throws JsonException
     */
    public function toArray(): array
    {
        return [
            'contaCorrente' => $this->account,
            'tipoConta' => $this->accountType?->name,
            'cpfCnpj' => $this->cpfCnpj,
            'agencia' => $this->agency,
            'nome' => $this->name,
            'instituicaoFinanceira' => $this->financialInstitution?->toArray(),
            'tipo' => $this->type,
        ];
    }
    // Getters e Setters (opcional, se necessário)
    public function getAccount(): ?string
    {
        return $this->account;
    }
    public function setAccount(?string $account): void
    {
        $this->account = $account;
    }
    public function getAccountType(): ?AccountType
    {
        return $this->accountType;
    }
    public function setAccountType(?AccountType $accountType): void
    {
        $this->accountType = $accountType;
    }
    public function getCpfCnpj(): ?string
    {
        return $this->cpfCnpj;
    }
    public function setCpfCnpj(?string $cpfCnpj): void
    {
        $this->cpfCnpj = $cpfCnpj;
    }
    public function getAgency(): ?string
    {
        return $this->agency;
    }
    public function setAgency(?string $agency): void
    {
        $this->agency = $agency;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(?string $name): void
    {
        $this->name = $name;
    }
    public function getFinancialInstitution(): ?FinancialInstitution
    {
        return $this->financialInstitution;
    }
    public function setFinancialInstitution(?FinancialInstitution $financialInstitution): void
    {
        $this->financialInstitution = $financialInstitution;
    }
}