<?php

namespace Tighten\SolanaPhpSdk\Accounts\Did;

use Tighten\SolanaPhpSdk\Borsh;

/**
 * Class VerificationMethodStruct
 * 
 * This class represents a verification method for a Decentralized Identifier (DID) account.
 * It provides methods for creating and managing verification methods, signing and verifying messages, and other related operations.
 * @version 1.0
 * @package Tighten\SolanaPhpSdk\Accounts\Did
 * @license MIT
 * @author Eduardo Chongkan
 * @link https://chongkan.com
 * @see https://github.com/identity-com/sol-did/tree/develop/sol-did/client/packages/idl
 * @see https://explorer.solana.com/address/didso1Dpqpm4CsiCjzP766BGY89CAdD6ZBL68cRhFPc/anchor-program?cluster=devnet
 */
class VerificationMethodStruct
{
    use Borsh\BorshDeserializable;

    public const SCHEMA = [
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['fragment', 'string'],
                ['flags', 'u16'],
                ['methodType', 'u8'],
                ['keyData', 'bytes']
            ],
        ],
    ];
}
