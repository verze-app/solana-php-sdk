<?php

namespace Tighten\SolanaPhpSdk\Programs;

use Tighten\SolanaPhpSdk\Program;

class SolanaTokenProgram extends Program
{
    public const SOLANA_TOKEN_PROGRAM_ID = 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA';

    /**
     * @param $client
     */
    public function __construct($client)
    {
        parent::__construct($client);
    }

    /**
     * @param string $pubKey
     * @return mixed
     */
    public function getTokenAccountsByOwner(string $pubKey)
    {
        return $this->client->call('getTokenAccountsByOwner', [
            $pubKey,
            [
                'programId' => self::SOLANA_TOKEN_PROGRAM_ID,
            ],
            [
                'encoding' => 'jsonParsed',
            ],
        ]);
    }
}
