<?php

namespace Tighten\SolanaPhpSdk\Utils;

class IsOnCurve
{
    protected $naclLowLevel;
    protected $gf1;
    protected $I;

    public function __construct()
    {
        $this->naclLowLevel = new TweetNacl;

        $this->gf1 = $this->naclLowLevel->gf([1]);
        $this->I = $this->naclLowLevel->gf([
            0xa0b0, 0x4a0e, 0x1b27, 0xc4ee, 0xe478, 0xad2f, 0x1806, 0x2f43, 0xd7a7,
            0x3dfb, 0x0099, 0x2b4d, 0xdf0b, 0x4fc1, 0x2480, 0x2b83,
        ]);
    }

    /**
     * Check that a pubkey is on the curve
     *
     * @source Source: https://github.com/solana-labs/solana-web3.js/blob/d8c6267cb813ff7c3c8ada70b9576e1c85c6be7e/src/publickey.ts#L200-L261
     */
    public function __invoke($p): bool
    {
        $naclLowLevel = $this->naclLowLevel;

        $r = [
            $naclLowLevel->gf(),
            $naclLowLevel->gf(),
            $naclLowLevel->gf(),
            $naclLowLevel->gf(),
        ];

        $t = $this->naclLowLevel->gf();
        $chk = $this->naclLowLevel->gf();
        $num = $this->naclLowLevel->gf();
        $den = $this->naclLowLevel->gf();
        $den2 = $this->naclLowLevel->gf();
        $den4 = $this->naclLowLevel->gf();
        $den6 = $this->naclLowLevel->gf();

        $naclLowLevel->set25519($r[2], $this->gf1);
        $naclLowLevel->unpack25519($r[1], $p);
        $naclLowLevel->S($num, $r[1]);
        $naclLowLevel->M($den, $num, $naclLowLevel->D);
        $naclLowLevel->Z($num, $num, $r[2]);
        $naclLowLevel->A($den, $r[2], $den);

        $naclLowLevel->S($den2, $den);
        $naclLowLevel->S($den4, $den2);
        $naclLowLevel->M($den6, $den4, $den2);
        $naclLowLevel->M($t, $den6, $num);
        $naclLowLevel->M($t, $t, $den);

        $naclLowLevel->pow2523($t, $t);
        $naclLowLevel->M($t, $t, $num);
        $naclLowLevel->M($t, $t, $den);
        $naclLowLevel->M($t, $t, $den);
        $naclLowLevel->M($r[0], $t, $den);

        $naclLowLevel->S($chk, $r[0]);
        $naclLowLevel->M($chk, $chk, $den);

        if ($this->neq25519($chk, $num)) {
            $naclLowLevel->M($r[0], $r[0], $this->I);
        }

        $naclLowLevel->S($chk, $r[0]);
        $naclLowLevel->M($chk, $chk, $den);

        return ! $this->neq25519($chk, $num);
    }

    /**
     * Check that two things are... not equal in 25519? maybe? I'm not really sure.
     *
     * "The Uint8Array typed array represents an array of 8-bit unsigned integers."
     */
    public function neq25519($a, $b): bool
    {
        // Create two Uint8Arrays with length of 32
        // @todo: Can we just make it 32-item-long arrays full of 0's?
        // $c = new Uint8Array(32);
        // $d = new Uint8Array(32);
        $c = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $d = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        $this->naclLowLevel->pack25519($c, $a);
        $this->naclLowLevel->pack25519($d, $b);

        return $this->naclLowLevel->crypto_verify_32($c, 0, $d, 0);
    }
}
