<?php

namespace Tighten\SolanaPhpSdk\Tests\Unit;

use Tighten\SolanaPhpSdk\Keypair;
use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\Tests\TestCase;

class PublicKeyTest extends TestCase
{
    /** @test */
    public function it_correctly_encodes_string_to_buffer()
    {
        $publicKey = new PublicKey('2ZC8EZduQGavJB9duMUgpdjNj7TQUiMawb52CLXBH5yc');

        $this->assertEquals([
            23, 26, 218, 1, 26, 7, 253, 202, 19, 162, 251, 121, 172, 0, 65, 219, 142, 20, 252, 217, 6, 150, 142, 0, 54, 146, 245, 140, 155, 194, 42, 131,
        ], $publicKey->toBuffer()->toArray());

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

    /** @test */
    public function it_equals()
    {
        $arrayKey = new PublicKey([3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,]);

        $base58Key = new PublicKey('CiDwVBFgWV9E5MvXWoLgnEgn2hK7rJikbvfWavzAQz3');

        $this->assertEquals($base58Key, $arrayKey);
    }

    /** @test */
    public function it_toBase58()
    {
        $key1 = new PublicKey('CiDwVBFgWV9E5MvXWoLgnEgn2hK7rJikbvfWavzAQz3');
        $this->assertEquals('CiDwVBFgWV9E5MvXWoLgnEgn2hK7rJikbvfWavzAQz3', $key1->toBase58());
        $this->assertEquals('CiDwVBFgWV9E5MvXWoLgnEgn2hK7rJikbvfWavzAQz3', $key1);

        $key2 = new PublicKey('1111111111111111111111111111BukQL');
        $this->assertEquals('1111111111111111111111111111BukQL', $key2->toBase58());
        $this->assertEquals('1111111111111111111111111111BukQL', $key2);

        $key3 = new PublicKey('11111111111111111111111111111111');
        $this->assertEquals('11111111111111111111111111111111', $key3->toBase58());

        $key4 = new PublicKey([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,]);
        $this->assertEquals('11111111111111111111111111111111', $key4->toBase58());
    }

    /** @test */
    public function it_createWithSeed()
    {
        $defaultPublicKey = new PublicKey('11111111111111111111111111111111');
        $derivedKey = PublicKey::createWithSeed($defaultPublicKey, 'limber chicken: 4/45', $defaultPublicKey);

        $this->assertEquals(new PublicKey('9h1HyLCW5dZnBVap8C5egQ9Z6pHyjsh5MNy83iPqqRuq'), $derivedKey);
    }

    /** @test */
    public function it_createProgramAddress()
    {
        $programId = new PublicKey('BPFLoader1111111111111111111111111111111111');
        $publicKey = new PublicKey('SeedPubey1111111111111111111111111111111111');

        $programAddress = PublicKey::createProgramAddress([
            '',
            [1],
        ], $programId);
        $this->assertEquals(new PublicKey('3gF2KMe9KiC6FNVBmfg9i267aMPvK37FewCip4eGBFcT'), $programAddress);

        $programAddress = PublicKey::createProgramAddress([
            '☉',
        ], $programId);
        $this->assertEquals(new PublicKey('7ytmC1nT1xY4RfxCV2ZgyA7UakC93do5ZdyhdF3EtPj7'), $programAddress);

        $programAddress = PublicKey::createProgramAddress([
            'Talking',
            'Squirrels',
        ], $programId);
        $this->assertEquals(new PublicKey('HwRVBufQ4haG5XSgpspwKtNd3PC9GM9m1196uJW36vds'), $programAddress);

        $programAddress = PublicKey::createProgramAddress([
            $publicKey->toBytes(),
        ], $programId);
        $this->assertEquals(new PublicKey('GUs5qLUfsEHkcMB9T38vjr18ypEhRuNWiePW2LoK4E3K'), $programAddress);
    }

    /** @test */
    public function it_findProgramAddress()
    {
        $programId = new PublicKey('BPFLoader1111111111111111111111111111111111');

        list($programAddress, $nonce) = PublicKey::findProgramAddress(
            [''],
            $programId
        );

        $this->assertEquals(
            PublicKey::createProgramAddress([
                '',
                [$nonce],
            ], $programId),
            $programAddress
        );
    }

    /** @test */
    public function it_isOnCurve()
    {
        $onCurvePublicKey = Keypair::generate()->getPublicKey();
        $this->assertTrue(PublicKey::isOnCurve($onCurvePublicKey));

        // A program address, yanked from one of the above tests. This is a pretty
        // poor test vector since it was created by the same code it is testing.
        // Unfortunately, I've been unable to find a golden negative example input
        // for curve25519 point decompression :/
        $offCurve = new PublicKey('12rqwuEgBYiGhBrDJStCiqEtzQpTTiZbh7teNVLuYcFA');
        $this->assertFalse(PublicKey::isOnCurve($offCurve));
    }
}
