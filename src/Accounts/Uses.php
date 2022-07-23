<?php

namespace Tighten\SolanaPhpSdk\Accounts;

use Tighten\SolanaPhpSdk\Borsh;

class Uses
{
    use Borsh\BorshDeserializable;

    public const SCHEMA = [
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['useMethod', 'u8'],
                ['remaining', 'u64'],
                ['total', 'u64']
            ],
        ],
    ];
}
