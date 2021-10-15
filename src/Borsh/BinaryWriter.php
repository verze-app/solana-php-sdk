<?php

namespace Tighten\SolanaPhpSdk\Borsh;

use Tighten\SolanaPhpSdk\Exceptions\TodoException;
use Tighten\SolanaPhpSdk\Util\Buffer;
use Closure;

class BinaryWriter
{
    protected Buffer $buffer;
    protected int $length;

    public function __construct()
    {
        $this->buffer = Buffer::from();
        $this->length = 0;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeU8(int $value)
    {
        $this->buffer->push($value, 1);
        $this->length += 1;
        return $this;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeU16(int $value)
    {
        $this->buffer->push($value, 2);
        $this->length += 2;
        return $this;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeU32(int $value)
    {
        $this->buffer->push($value, 4);
        $this->length += 4;
        return $this;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeU64(int $value)
    {
        $this->buffer->push($value, 8);
        $this->length += 8;
        return $this;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeU128(int $value)
    {
        $this->buffer->push($value, 16);
        $this->length += 16;
        return $this;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeU256(int $value)
    {
        $this->buffer->push($value, 32);
        $this->length += 32;
        return $this;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeU512(int $value)
    {
        $this->buffer->push($value, 64);
        $this->length += 64;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function writeString(string $value)
    {
        $valueBuffer = Buffer::from($value);
        $this->writeU32(sizeof($valueBuffer));
        $this->writeBuffer($valueBuffer);
        return $this;
    }

    /**
     * @param array $array
     * @return $this
     */
    public function writeFixedArray(array $array)
    {
        $this->writeBuffer(Buffer::from($array));
        return $this;
    }

    /**
     * @param array $array
     * @return $this
     */
    public function writeArray(array $array, Closure $writeFn)
    {
        $this->writeU32(sizeof($array));
        foreach ($array as $item) {
            $writeFn($item);
        }
        return $this;
    }

    /**
     * @param Buffer $buffer
     * @return $this
     */
    protected function writeBuffer(Buffer $buffer)
    {
        $this->buffer->push($buffer);
        $this->length += sizeof($buffer);
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->buffer->slice(0, $this->length)->toArray();
    }
}
