<?php

namespace Tighten\SolanaPhpSdk\Accounts;

use Tighten\SolanaPhpSdk\Borsh;

class Collection
{
    use Borsh\BorshDeserializable;

    public const SCHEMA = [
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['verified', 'u8'],
                ['key', 'pubkeyAsString'],
            ],
        ],
    ];
}
