<?php

namespace Tighten\SolanaPhpSdk\Util;

use Tighten\SolanaPhpSdk\PublicKey;

class Signer
{
    public PublicKey $publicKey;
    public string $secretKey;

    public function __construct(PublicKey $publicKey, string $secretKey)
    {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
    }
}
