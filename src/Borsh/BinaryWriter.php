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
        return $this->writeBuffer(Buffer::from($value, Buffer::TYPE_BYTE, false));
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeU16(int $value)
    {
        return $this->writeBuffer(Buffer::from($value, Buffer::TYPE_SHORT, false));
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeU32(int $value)
    {
        return $this->writeBuffer(Buffer::from($value, Buffer::TYPE_INT, false));
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeU64(int $value)
    {
        return $this->writeBuffer(Buffer::from($value, Buffer::TYPE_LONG, false));
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeI8(int $value)
    {
        return $this->writeBuffer(Buffer::from($value, Buffer::TYPE_BYTE, true));
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeI16(int $value)
    {
        return $this->writeBuffer(Buffer::from($value, Buffer::TYPE_SHORT, true));
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeI32(int $value)
    {
        return $this->writeBuffer(Buffer::from($value, Buffer::TYPE_INT, true));
    }

    /**
     * @param int $value
     * @return $this
     */
    public function writeI64(int $value)
    {
        return $this->writeBuffer(Buffer::from($value, Buffer::TYPE_LONG, true));
    }

    /**
     * @param float $value
     * @return $this
     */
    public function writeF32(float $value)
    {
        return $this->writeBuffer(Buffer::from($value, Buffer::TYPE_FLOAT, true)->fixed(4));
    }

    /**
     * @param float $value
     * @return $this
     */
    public function writeF64(float $value)
    {
        return $this->writeBuffer(Buffer::from($value, Buffer::TYPE_FLOAT, true)->fixed(8));
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
