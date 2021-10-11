<?php

namespace Tighten\SolanaPhpSdk\Util;

use Tighten\SolanaPhpSdk\PublicKey;

interface HasPublicKey
{
    public function getPublicKey(): PublicKey;
}
