<?php

namespace Tighten\SolanaPhpSdk\Util;

use Tighten\SolanaPhpSdk\PublicKey;

class SignaturePubkeyPair
{
    public PublicKey $publicKey;
    public ?string $signature;

    public function __construct(PublicKey $publicKey, ?string $signature = null)
    {
        $this->publicKey = $publicKey;
        $this->signature = $signature;
    }
}
