<?php

namespace Tighten\SolanaPhpSdk\Util;

interface HasSecretKey
{
    public function getSecretKey(): Buffer;
}
