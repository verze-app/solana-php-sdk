<?php

namespace Tighten\SolanaPhpSdk\Programs;

use Tighten\SolanaPhpSdk\Program;

class MetaplexProgram extends Program
{
    public const METAPLEX_PROGRAM_ID = 'metaqbxxUerdq28cj1RbAWkYQm3ybzjb6a8bt518x1s';

    /**
     * @param string $pubKey
     * @return array|mixed
     */
    public function getProgramAccounts(string $pubKey)
    {
        $magicOffsetNumber = 326; // 🤷‍♂️

        return $this->client->call('getProgramAccounts', [
            self::METAPLEX_PROGRAM_ID,
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
        ]);
    }
}
