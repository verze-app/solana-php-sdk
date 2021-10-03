<?php

namespace Tighten\SolanaPhpSdk\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mockery as M;
use Tighten\SolanaPhpSdk\Exceptions\AccountNotFoundException;
use Tighten\SolanaPhpSdk\Programs\SystemProgram;
use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\SolanaRpcClient;
use Tuupola\Base58;

class PublicKeyTest extends TestCase
{
    /** @test */
    public function it_correctly_encodes_string_to_buffer()
    {
        $publicKey = new PublicKey('2ZC8EZduQGavJB9duMUgpdjNj7TQUiMawb52CLXBH5yc');

        $this->assertEquals([
            23, 26, 218, 1, 26, 7, 253, 202, 19, 162, 251, 121, 172, 0, 65, 219, 142, 20, 252, 217, 6, 150, 142, 0, 54, 146, 245, 140, 155, 194, 42, 131,
        ], $publicKey->toBuffer());

        $this->assertEquals('2ZC8EZduQGavJB9duMUgpdjNj7TQUiMawb52CLXBH5yc', $publicKey->toBase58());
    }

    /** @test */
    public function it_correctly_evaluates_equality()
    {
        $publicKey1 = new PublicKey('2ZC8EZduQGavJB9duMUgpdjNj7TQUiMawb52CLXBH5yc');
        $publicKey2 = new PublicKey('2ZC8EZduQGavJB9duMUgpdjNj7TQUiMawb52CLXBH5yc');

        $this->assertEquals($publicKey1, $publicKey2);
    }

    /** @test */
    public function it_correctly_handles_public_key_in_constructor()
    {
        $publicKey1 = new PublicKey('2ZC8EZduQGavJB9duMUgpdjNj7TQUiMawb52CLXBH5yc');
        $publicKey2 = new PublicKey($publicKey1);

        $this->assertEquals($publicKey1, $publicKey2);
    }
}
