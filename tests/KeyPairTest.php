<?php

namespace Tighten\SolanaPhpSdk\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mockery as M;
use Tighten\SolanaPhpSdk\Exceptions\AccountNotFoundException;
use Tighten\SolanaPhpSdk\KeyPair;
use Tighten\SolanaPhpSdk\Programs\SystemProgram;
use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\SolanaRpcClient;
use Tighten\SolanaPhpSdk\Util\Ed25519Keypair;

class KeyPairTest extends TestCase
{
    /**
     * Seeded from
     * https://github.com/solana-labs/solana-web3.js/blob/master/test/keypair.test.ts
     * on Oct 2, 2021
     */

    /** @test */
    public function it_new_keypair()
    {
        $keyPair = new KeyPair();

        $this->assertEquals(64, sizeof($keyPair->getSecretKey()));
        $this->assertEquals(32, sizeof($keyPair->getPublicKey()->toBytes()));
    }

    /** @test */
    public function it_generate_new_keypair()
    {
        $keyPair = KeyPair::generate();

        $this->assertEquals(64, sizeof($keyPair->getSecretKey()));
        $this->assertEquals(32, sizeof($keyPair->getPublicKey()->toBytes()));
    }

    /** @test */
    public function it_keypair_from_secret_key()
    {
        $secretKey = sodium_base642bin('mdqVWeFekT7pqy5T49+tV12jO0m+ESW7ki4zSU9JiCgbL0kJbj5dvQ/PqcDAzZLZqzshVEs01d1KZdmLh4uZIg==', SODIUM_BASE64_VARIANT_ORIGINAL);

        $keyPair = KeyPair::fromSecretKey($secretKey);

        $this->assertEquals('2q7pyhPwAwZ3QMfZrnAbDhnh9mDUqycszcpf86VgQxhF', $keyPair->getPublicKey()->toBase58());
    }

    /** @test */
    public function it_generate_keypair_from_seed()
    {
        $byteArray = array_fill(0, 32, 8);

        $seedString = pack('C*', ...$byteArray);

        $keyPair = KeyPair::fromSeed($seedString);

        $this->assertEquals('2KW2XRd9kwqet15Aha2oK3tYvd3nWbTFH1MBiRAv1BE1', $keyPair->getPublicKey()->toBase58());
    }
}
