<?php

namespace Tighten\SolanaPhpSdk\Tests\Unit;

use Tighten\SolanaPhpSdk\Keypair;
use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\Tests\TestCase;
use Tighten\SolanaPhpSdk\Programs\SplTokenProgram;

class SplTokenProgramTest extends TestCase
{
    /** @test */
    public function it_hasCorrectProgramIds()
    {
        $this->assertEquals('TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA', SplTokenProgram::SOLANA_TOKEN_PROGRAM_ID);
        $this->assertEquals('TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA', SplTokenProgram::programId()->toBase58());

        $this->assertEquals('ATokenGPvbdGVxr1b2hvZbsiqW5xWH25efTNsLJA8knL', SplTokenProgram::SOLANA_ASSOCIATED_TOKEN_PROGRAM_ID);
        $this->assertEquals('ATokenGPvbdGVxr1b2hvZbsiqW5xWH25efTNsLJA8knL', SplTokenProgram::associatedProgramId()->toBase58());
    }

    /** @test */
    public function it_getAssociatedTokenAddress()
    {
        $mint = new PublicKey('2hvKBnhXZVvZadKX5QKkiyU7pVXXe5ZNMZhAoeXJdHxj');
        $owner = new PublicKey('Gk9HLj4qg1nTu2s7cdpm6PPgosxqUjya1cbhif4Lo6qv');

        $tokenAccount = SplTokenProgram::getAssociatedTokenAddress(
            SplTokenProgram::associatedProgramId(),
            SplTokenProgram::programId(),
            $mint,
            $owner,
            true
        );

        $this->assertEquals('27f5ubUFNrfXBwgqrH1sKMPuQVmm99qUuZxuJNtd5As4', $tokenAccount->toBase58());
    }
}
