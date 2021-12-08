<?php

namespace Tighten\SolanaPhpSdk\Accounts;

use Tighten\SolanaPhpSdk\Borsh\Borsh;
use Tighten\SolanaPhpSdk\Borsh\BorshDeserializable;

class Metadata
{
    use BorshDeserializable;

    public const SCHEMA = [
        Creator::class => Creator::SCHEMA[Creator::class],
        MetadataData::class => MetadataData::SCHEMA[MetadataData::class],
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['key', 'u8'],
                ['updateAuthority', 'pubkeyAsString'],
                ['mint', 'pubkeyAsString'],
                ['data', MetadataData::class],
                ['primarySaleHappened', 'u8'], // bool
                ['isMutable', 'u8'], // bool
            ],
        ],
    ];

    public static function fromBuffer(array $buffer): self
    {
        return Borsh::deserialize(self::SCHEMA, self::class, $buffer);
    }
}
