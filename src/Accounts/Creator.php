<?php

namespace Tighten\SolanaPhpSdk\Accounts;

use Tighten\SolanaPhpSdk\Borsh;

class Creator
{
    use Borsh\BorshDeserializable;

    public const SCHEMA = [
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['address', 'pubkeyAsString'],
                ['verified', 'u8'],
                ['share', 'u8'],
            ],
        ],
    ];
}
