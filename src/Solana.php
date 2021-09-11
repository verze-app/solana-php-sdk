<?php

namespace Tighten\SolanaPhpSdk;

use Illuminate\Http\Client\Response;

class Solana
{
    protected $client;

    public function __construct(SolanaRpcClient $client)
    {
        $this->client = $client;
    }

    public function getAccountInfo(string $pubKey): array
    {
        return $this->client->call('getAccountInfo', [$pubKey])->json()['result']['value'];
    }

    public function getBalance(string $pubKey): Float
    {
        return $this->client->call('getBalance', [$pubKey])['result']['value'];
    }

    public function getConfirmedTransaction(string $transactionSignature): array
    {
        return $this->client->call('getConfirmedTransaction', [$transactionSignature])['result'];
    }

    // NEW: This method is only available in solana-core v1.7 or newer. Please use getConfirmedTransaction for solana-core v1.6
    public function getTransaction(string $transactionSignature): array
    {
        return $this->client->call('getTransaction', [$transactionSignature])['result'];
    }

    public function __call($method, array $params = []): Response
    {
        return $this->client->call($method, $params)->json();
    }
}
