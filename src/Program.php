<?php

namespace Tighten\SolanaPhpSdk;

class Program
{
    /**
     * @var SolanaRpcClient
     */
    protected SolanaRpcClient $client;

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function __call($method, array $params = []): ?array
    {
        return $this->client->call($method, $params)->json();
    }
}
