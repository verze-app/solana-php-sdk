<?php

namespace Tighten\SolanaPhpSdk\Borsh;

trait BorshDeserializable
{
    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }
}
