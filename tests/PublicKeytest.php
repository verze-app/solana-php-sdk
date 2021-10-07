<?php

namespace Tighten\SolanaPhpSdk\Tests;

use PHPUnit\Framework\TestCase;
use Tighten\SolanaPhpSdk\Utils\PublicKey;

class PublicKeyTest extends TestCase
{
    /** @test */
    public function it_gets_program_id_for_mint_key()
    {
        $usableMintKey = '8rGDMnQs7SowWwZdNBURFUehQdSH2Jpo3JbrSzhjQ9xL';
        $publicKey = new PublicKey();

        $output = $publicKey->findProgramAddressViaJavascript($usableMintKey);

        $this->assertEquals('HWFGHxRiQZvd9Mr7721cs32DAqavcQ3K2KuSGT66jRbk', $output);
    }
}
