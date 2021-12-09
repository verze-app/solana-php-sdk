<?php

namespace Tighten\SolanaPhpSdk;

class Program
{
    /**
     * @var SolanaRpcClient
     */
    protected SolanaRpcClient $client;

    public function __construct(SolanaRpcClient $client)
    {
        $this->client = $client;
    }

    public function __call($method, $params = [])
    {
        return $this->client->call($method, ...$params);
    }
}
