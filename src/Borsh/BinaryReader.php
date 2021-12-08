<?php

namespace Tighten\SolanaPhpSdk\Borsh;

use Tighten\SolanaPhpSdk\Exceptions\TodoException;
use Tighten\SolanaPhpSdk\Util\Buffer;
use Tighten\SolanaPhpSdk\PublicKey;
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
        return $this->readUnsignedInt(1, Buffer::TYPE_BYTE);
    }

    /**
     * @return int
     */
    public function readU16(): int
    {
        return $this->readUnsignedInt(2, Buffer::TYPE_SHORT);
    }

    /**
     * @return int
     */
    public function readU32(): int
    {
        return $this->readUnsignedInt(4, Buffer::TYPE_INT);
    }

    /**
     * @return int
     */
    public function readU64(): int
    {
        return $this->readUnsignedInt(8, Buffer::TYPE_LONG);
    }

    /**
     * @return int
     */
    public function readI8(): int
    {
        return $this->readSignedInt(1, Buffer::TYPE_BYTE);
    }

    /**
     * @return int
     */
    public function readI16(): int
    {
        return $this->readSignedInt(2, Buffer::TYPE_SHORT);
    }

    /**
     * @return int
     */
    public function readI32(): int
    {
        return $this->readSignedInt(4, Buffer::TYPE_INT);
    }

    /**
     * @return int
     */
    public function readI64(): int
    {
        return $this->readSignedInt(8, Buffer::TYPE_LONG);
    }

    /**
     * @param $length
     * @return int
     */
    protected function readUnsignedInt($length, $datatype): int
    {
        $value = $this->buffer->slice($this->offset, $length, $datatype, false)->value();
        $this->offset += $length;
        return $value;
    }

    /**
     * @param $length
     * @return int
     */
    protected function readSignedInt($length, $datatype): int
    {
        $value = $this->buffer->slice($this->offset, $length, $datatype, true)->value();
        $this->offset += $length;
        return $value;
    }

    /**
     * @return float
     */
    public function readF32(): float
    {
        $value = $this->buffer->slice($this->offset, 4, Buffer::TYPE_FLOAT, true)->value();
        $this->offset += 4;
        return $value;
    }

    /**
     * @return float
     */
    public function readF64(): float
    {
        $value = $this->buffer->slice($this->offset, 8, Buffer::TYPE_FLOAT, true)->value();
        $this->offset += 8;
        return $value;
    }

    /**
     * @return string
     * @throws BorshException
     */
    public function readString(): string
    {
        $length = $this->readU32();
        return $this->readBuffer($length);
    }

    /**
     * @param int $length
     * @return array
     */
    public function readFixedArray(int $length): array
    {
        return $this->readBuffer($length)->toArray();
    }

    public function readPubKey(): PublicKey
    {
        return new PublicKey($this->readFixedArray(32));
    }

    public function readPubKeyAsString(): string
    {
        return $this->readPubKey()->toBase58();
    }

    /**
     * @return array
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
