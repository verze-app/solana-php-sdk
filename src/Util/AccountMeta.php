<?php

namespace Tighten\SolanaPhpSdk\Util;

use Tighten\SolanaPhpSdk\PublicKey;

class AccountMeta implements HasPublicKey
{
    protected PublicKey $publicKey;
    public bool $isSigner;
    public bool $isWritable;

    public function __construct($publicKey, $isSigner, $isWritable)
    {
        $this->publicKey = $publicKey;
        $this->isSigner = $isSigner;
        $this->isWritable = $isWritable;
    }

    public function getPublicKey(): PublicKey
    {
        return $this->publicKey;
    }
}
