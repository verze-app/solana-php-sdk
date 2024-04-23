<?php

namespace Tighten\SolanaPhpSdk\Accounts;

use Tighten\SolanaPhpSdk\Accounts\Did\VerificationMethodStruct;
use Tighten\SolanaPhpSdk\Accounts\Did\ServiceStruct;
use Tighten\SolanaPhpSdk\Borsh\Borsh;
use Tighten\SolanaPhpSdk\Borsh\BorshDeserializable;

/**
 * Class DidData
 * 
 * This class represents a Decentralized Identifier (DID) account.
 * It provides methods for creating and managing DID accounts, signing and verifying messages, and other related operations.
 * @version 1.0
 * @package Tighten\SolanaPhpSdk\Accounts
 * @license MIT
 * @author Eduardo Chongkan
 * @link https://chongkan.com
 * @see https://github.com/identity-com/sol-did/tree/develop/sol-did/client/packages/idl
 * @see https://explorer.solana.com/address/didso1Dpqpm4CsiCjzP766BGY89CAdD6ZBL68cRhFPc/anchor-program?cluster=devnet
 */

class DidData
{

    use BorshDeserializable;


    public const SCHEMA = [
        VerificationMethodStruct::class => VerificationMethodStruct::SCHEMA[VerificationMethodStruct::class],
        ServiceStruct::class => ServiceStruct::SCHEMA[ServiceStruct::class],
        self::class => [
            'kind' => 'struct',
            'fields' => [
                ['offset', 'u64'],
                ['version', 'u8'],
                ['bump', 'u8'],
                ['nonce', 'u64'],
                ['initialVerificationMethod', 'string'],
                ['flags', 'u16'],
                ['methodType', 'u8'],
                ['keyData', ['u8']],
                ['verificationMethods', [VerificationMethodStruct::class]],
                ['services', [ServiceStruct::class]],
                ['nativeControllers', ['pubKey']],
                ['otherControllers', ['string']],
            ],
        ],
    ];

    public static function fromBuffer(array $buffer): self
    {
        return Borsh::deserialize(self::SCHEMA, self::class, $buffer);
    }
}
