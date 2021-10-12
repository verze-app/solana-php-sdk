<?php

namespace Tighten\SolanaPhpSdk\Tests\Unit;

use Tighten\SolanaPhpSdk\Keypair;
use Tighten\SolanaPhpSdk\Tests\TestCase;
use Tighten\SolanaPhpSdk\Util\Buffer;

class KeypairTest extends TestCase
{
    /**
     * Seeded from
     * https://github.com/solana-labs/solana-web3.js/blob/master/test/keypair.test.ts
     * on Oct 2, 2021
     */

    /** @test */
    public function it_new_keypair()
    {
        $keypair = new Keypair();

        $this->assertEquals(64, sizeof($keypair->getSecretKey()));
        $this->assertEquals(32, sizeof($keypair->getPublicKey()->toBytes()));
    }

    /** @test */
    public function it_generate_new_keypair()
    {
        $keypair = Keypair::generate();

        $this->assertEquals(64, sizeof($keypair->getSecretKey()));
        $this->assertEquals(32, sizeof($keypair->getPublicKey()->toBytes()));
    }

    /** @test */
    public function it_keypair_from_secret_key()
    {
        $secretKey = sodium_base642bin('mdqVWeFekT7pqy5T49+tV12jO0m+ESW7ki4zSU9JiCgbL0kJbj5dvQ/PqcDAzZLZqzshVEs01d1KZdmLh4uZIg==', SODIUM_BASE64_VARIANT_ORIGINAL);

        $keypair = Keypair::fromSecretKey($secretKey);

        $this->assertEquals('2q7pyhPwAwZ3QMfZrnAbDhnh9mDUqycszcpf86VgQxhF', $keypair->getPublicKey()->toBase58());
    }

    /** @test */
    public function it_generate_keypair_from_seed()
    {
        $byteArray = array_fill(0, 32, 8);

        $seedString = pack('C*', ...$byteArray);

        $keypair = Keypair::fromSeed($seedString);

        $this->assertEquals('2KW2XRd9kwqet15Aha2oK3tYvd3nWbTFH1MBiRAv1BE1', $keypair->getPublicKey()->toBase58());
    }

    /** @test */
    public function it_bin2array_and_array2bin_are_equivalent()
    {
        $keypair = sodium_crypto_sign_keypair();
        $publicKey = sodium_crypto_sign_publickey($keypair);

        $valueAsArray = Buffer::from($publicKey)->toArray();
        $valueAsString = Buffer::from($valueAsArray)->toString();

        $this->assertEquals($publicKey, $valueAsString);
    }
}
