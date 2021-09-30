<?php

namespace Tighten\SolanaPhpSdk\Programs;

use Tighten\SolanaPhpSdk\Program;

class SolanaProgram extends Program
{
    /**
     * @param $client
     */
    public function __construct($client)
    {
        parent::__construct($client);
    }

    /**
     * @param string $pubKey
     * @return array
     */
    public function getAccountInfo(string $pubKey): array
    {
        return $this->client->call('getAccountInfo', [$pubKey, ["encoding" => "jsonParsed"]])['value'];
    }

    /**
     * @param string $pubKey
     * @return float
     */
    public function getBalance(string $pubKey): float
    {
        return $this->client->call('getBalance', [$pubKey])['value'];
    }

    /**
     * @param string $transactionSignature
     * @return array
     */
    public function getConfirmedTransaction(string $transactionSignature): array
    {
        return $this->client->call('getConfirmedTransaction', [$transactionSignature]);
    }

    /**
     * NEW: This method is only available in solana-core v1.7 or newer. Please use getConfirmedTransaction for solana-core v1.6
     *
     * @param string $transactionSignature
     * @return array
     */
    public function getTransaction(string $transactionSignature): array
    {
        return $this->client->call('getTransaction', [$transactionSignature]);
    }
}
