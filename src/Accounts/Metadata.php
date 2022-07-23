<?php

namespace Tighten\SolanaPhpSdk\Accounts;

use Tighten\SolanaPhpSdk\Borsh\Borsh;
use Tighten\SolanaPhpSdk\Borsh\BorshDeserializable;

class Metadata
{
    use BorshDeserializable;

    public const SCHEMA = [
        Creator::class => Creator::SCHEMA[Creator::class],
        Collection::class => Collection::SCHEMA[Collection::class],
        MetadataData::class => MetadataData::SCHEMA[MetadataData::class],
        Uses::class => Uses::SCHEMA[Uses::class],
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['key', 'u8'],
                ['updateAuthority', 'pubkeyAsString'],
                ['mint', 'pubkeyAsString'],
                ['data', MetadataData::class],
                ['primarySaleHappened', 'u8'], // bool
                ['isMutable', 'u8'], // bool
                ['editionNonce', [
                    'kind' => 'option',
                    'type' => 'u8'
                ]],
                ['tokenStandard', [
                    'kind' => 'option',
                    'type' => 'u8'
                ]],
                ['collection', [
                    'kind' => 'option',
                    'type' => Collection::class
                ]],
                ['uses', [
                    'kind' => 'option',
                    'type' => Uses::class
                ]],
            ],
        ],
    ];

    public static function fromBuffer(array $buffer): self
    {
        return Borsh::deserialize(self::SCHEMA, self::class, $buffer);
    }
}
