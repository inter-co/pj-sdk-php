<?php

namespace Inter\Sdk\sdkLibrary\banking\models;

abstract class Recipient
{
    public static function createFromJson(mixed $json): ?self {
        if (isset($json['tipo']) && $json['tipo'] === 'CHAVE') {
            return Key::fromJson($json);
        } else if (isset($json['tipo']) && $json['tipo'] === 'DADOS_BANCARIOS') {
            return BankDetails::fromJson($json);
        } else {
            return CopyAndPaste::fromJson($json);
        }
    }
}