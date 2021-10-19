<?php

namespace Tighten\SolanaPhpSdk\Borsh;

trait BorshDeserializable
{
    /**
     * Create a new instance of this object.
     *
     * Note: must override when the default constructor required parameters!
     *
     * @return $this
     */
    public static function borshConstructor()
    {
        return new static();
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }
}
