<?php

namespace Tighten\SolanaPhpSdk\Util;

use Tighten\SolanaPhpSdk\PublicKey;

class SignaturePubkeyPair
{
    public PublicKey $publicKey;
    public ?array $signature;

    public function __construct(PublicKey $publicKey, ?array $signature = null)
    {
        $this->publicKey = $publicKey;
        $this->signature = $signature;
    }
}
