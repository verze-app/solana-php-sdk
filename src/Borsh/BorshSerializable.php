<?php

namespace Tighten\SolanaPhpSdk\Borsh;

trait BorshSerializable
{
    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->{$name};
    }
}
