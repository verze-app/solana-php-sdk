<?php

namespace Tighten\SolanaPhpSdk\Borsh;

trait BorshSerializable
{
    public function __get($name)
    {
        return $this->{$name};
    }
}
