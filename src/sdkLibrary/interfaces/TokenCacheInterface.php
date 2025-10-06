<?php 

namespace Inter\Sdk\sdkLibrary\interfaces;

interface TokenCacheInterface
{
    public function get(string $key): ?string;
    public function set(string $key, string $value, int $ttl): void;
    public function delete(string $key): void;
}
