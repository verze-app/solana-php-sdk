<?php

namespace Tighten\SolanaPhpSdk\Utils;

use StephenHill\Base58 as Converter;

class Base58
{
    public static function encode($data)
    {
        $base58 = new Converter();
        return $base58->encode($data);
    }

    public static function decode($data)
    {
        $base58 = new Converter();
        return $base58->decode($data);
    }
}
