<?php

namespace Tighten\SolanaPhpSdk\Programs;

use Tighten\SolanaPhpSdk\Program;
use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\Util\AccountMeta;
use Tighten\SolanaPhpSdk\Programs\SystemProgram;
use Tighten\SolanaPhpSdk\TransactionInstruction;
use Tighten\SolanaPhpSdk\Exceptions\InputValidationException;

class SplTokenProgram extends Program
{
    public const SOLANA_TOKEN_PROGRAM_ID = 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA';

    public const SOLANA_ASSOCIATED_TOKEN_PROGRAM_ID = 'ATokenGPvbdGVxr1b2hvZbsiqW5xWH25efTNsLJA8knL';

    public static function programId(): PublicKey
    {
        return new PublicKey(self::SOLANA_TOKEN_PROGRAM_ID);
    }

    public static function associatedProgramId(): PublicKey
    {
        return new PublicKey(self::SOLANA_ASSOCIATED_TOKEN_PROGRAM_ID);
    }

    /**
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

    public static function getAssociatedTokenAddress(
        PublicKey $associatedProgramId,
        PublicKey $programId,
        PublicKey $mint,
        PublicKey $owner,
        bool $allowOwnerOffCurve = false
    ): PublicKey {
        if (! $allowOwnerOffCurve && ! PublicKey::isOnCurve($owner)) {
            throw new InputValidationException("Owner cannot sign: {$owner->toBase58()}");
        }

        return PublicKey::findProgramAddress([
            $owner->toBuffer(),
            $programId->toBuffer(),
            $mint->toBuffer()
        ], $associatedProgramId)[0];
    }

    public static function createAssociatedTokenAccountInstruction(
        PublicKey $associatedProgramId,
        PublicKey $programId,
        PublicKey $mint,
        PublicKey $associatedAccount,
        PublicKey $owner,
        PublicKey $payer,
    ): TransactionInstruction {
        $data = new Buffer();

        $keys = [
            new AccountMeta($payer, true, true),
            new AccountMeta($associatedAccount, false, true),
            new AccountMeta($owner, false, false),
            new AccountMeta($mint, false, false),
            new AccountMeta(SystemProgram::programId(), false, false),
            new AccountMeta($programId, false, false),
            new AccountMeta(SysVar::rent(), false, false),
        ];

        return new TransactionInstruction(
            $associatedProgramId,
            $keys,
            $data
        );
    }
}
