<?php

namespace Tighten\SolanaPhpSdk;

use Tighten\SolanaPhpSdk\Exceptions\AccountNotFoundException;

class Solana
{
    public const solanaTokenProgramId = 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA';
    public const metaplexPublicKey = 'metaqbxxUerdq28cj1RbAWkYQm3ybzjb6a8bt518x1s';

    protected $client;

    public function __construct(SolanaRpcClient $client)
    {
        $this->client = $client;
    }

    public function getAccountInfo(string $pubKey): array
    {
        $accountResponse = $this->client->call('getAccountInfo', [$pubKey, ["encoding" => "jsonParsed"]])->json()['result']['value'];

        if (! $accountResponse) {
            throw new AccountNotFoundException("API Error: Account {$pubKey} not found.");
        }

        return $accountResponse;
    }

    public function getBalance(string $pubKey): float
    {
        return $this->client->call('getBalance', [$pubKey])['result']['value'];
    }

    public function getConfirmedTransaction(string $transactionSignature): array
    {
        return $this->client->call('getConfirmedTransaction', [$transactionSignature])['result'];
    }

    public function getProgramAccounts(string $pubKey)
    {
        $magicOffsetNumber = 326; // 🤷‍♂️

        return $this->client->call('getProgramAccounts', [
            self::metaplexPublicKey,
            [
                'encoding' => 'base64',
                'filters' => [
                    [
                        'memcmp' => [
                            'bytes' => $pubKey,
                            'offset' => $magicOffsetNumber,
                        ],
                    ],
                ],
            ],
        ])->json();
    }

    public function getTokenAccountsByOwner(string $pubKey)
    {
        return $this->client->call('getTokenAccountsByOwner', [
            $pubKey,
            [
                'programId' => self::solanaTokenProgramId,
            ],
            [
                'encoding' => 'jsonParsed',
            ],
        ])['result']['value'];
    }

    // NEW: This method is only available in solana-core v1.7 or newer. Please use getConfirmedTransaction for solana-core v1.6
    public function getTransaction(string $transactionSignature): array
    {
        return $this->client->call('getTransaction', [$transactionSignature])['result'];
    }

    public function __call($method, array $params = []): ?array
    {
        return $this->client->call($method, $params)->json();
    }
}
