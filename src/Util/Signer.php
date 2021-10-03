<?php

namespace Tighten\SolanaPhpSdk\Util;

use Tighten\SolanaPhpSdk\PublicKey;

class Signer
{
    public PublicKey $publicKey;
    public array $secretKey;

    public function __construct(PublicKey $publicKey, array $secretKey)
    {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
    }
}
