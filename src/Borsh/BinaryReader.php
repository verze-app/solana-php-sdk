<?php

namespace Tighten\SolanaPhpSdk\Borsh;

use Tighten\SolanaPhpSdk\Exceptions\TodoException;
use Tighten\SolanaPhpSdk\Util\Buffer;
use Closure;

class BinaryReader
{
    protected Buffer $buffer;
    protected int $offset;

    public function __construct(Buffer $buffer)
    {
        $this->buffer = $buffer;
        $this->offset = 0;
    }

    /**
     * @return int
     */
    public function readU8(): int
    {
        $valueArray = $this->buffer->slice($this->offset, 1);
        $value = $valueArray->toInt();
        $this->offset += 1;
        return $value;
    }

    /**
     * @return int
     */
    public function readU16(): int
    {
        $valueArray = $this->buffer->slice($this->offset, 2);
        $value = $valueArray->toInt();
        $this->offset += 2;
        return $value;
    }

    /**
     * @return int
     */
    public function readU32(): int
    {
        $valueArray = $this->buffer->slice($this->offset, 4);
        $value = $valueArray->toInt();
        $this->offset += 4;
        return $value;
    }

    /**
     * @return int
     */
    public function readU64(): int
    {
        $valueArray = $this->buffer->slice($this->offset, 8);
        $value = $valueArray->toInt();
        $this->offset += 8;
        return $value;
    }

    /**
     * @return int
     */
    public function readU128(): int
    {
        $valueArray = $this->buffer->slice($this->offset, 16);
        $value = $valueArray->toInt();
        $this->offset += 16;
        return $value;
    }

    /**
     * @return int
     */
    public function readU256(): int
    {
        $valueArray = $this->buffer->slice($this->offset, 32);
        $value = $valueArray->toInt();
        $this->offset += 32;
        return $value;
    }

    /**
     * @return int
     */
    public function readU512(): int
    {
        $valueArray = $this->buffer->slice($this->offset, 64);
        $value = $valueArray->toInt();
        $this->offset += 64;
        return $value;
    }

    /**
     * @return string
     * @throws BorshException
     */
    public function readString(): string
    {
        $length = $this->readU32();
        $buffer = $this->readBuffer($length);
        return pack('C*', ...$buffer->toArray());
    }

    /**
     * @param int $length
     * @return array
     */
    public function readFixedArray(int $length): array
    {
        return $this->readBuffer($length)->toArray();
    }

    /**
     * @return array
     * @throws TodoException
     */
    public function readArray(Closure $readEachItem): array
    {
        $length = $this->readU32();
        $array = [];
        for ($i = 0; $i < $length; $i++) {
            array_push($array, $readEachItem());
        }
        return $array;
    }

    /**
     * @param $length
     * @return Buffer
     * @throws BorshException
     */
    protected function readBuffer($length): Buffer
    {
        if ($this->offset + $length > sizeof($this->buffer)) {
            throw new BorshException("Expected buffer length {$length} isn't within bounds");
        }

        $buffer = $this->buffer->slice($this->offset, $length);
        $this->offset += $length;
        return $buffer;
    }

}
