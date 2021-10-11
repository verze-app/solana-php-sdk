<?php

namespace Tighten\SolanaPhpSdk\Util;

use Countable;
use Tighten\SolanaPhpSdk\Exceptions\InputValidationException;
use Tighten\SolanaPhpSdk\KeyPair;
use Tighten\SolanaPhpSdk\PublicKey;

class Buffer implements Countable
{
    /**
     * @var array<int>
     */
    protected array $data;

    /**
     * @param mixed $value
     */
    public function __construct($value = null)
    {
        if (is_string($value)) {
            // unpack returns an array indexed at 1.
            $this->data = array_values(unpack('C*', $value));
        } elseif (is_array($value)) {
            $this->data = $value;
        } elseif (is_numeric($value)) {
            $this->data = [$value];
        } elseif ($value instanceof PublicKey) {
            $this->data = $value->toBytes();
        } elseif ($value instanceof Buffer) {
            $this->data = $value->toArray();
        } elseif ($value == null) {
            $this->data = [];
        } elseif (method_exists($value, 'toArray')) {
            $this->data = $value->toArray();
        } else {
            throw new InputValidationException('Unsupported $value for Buffer: ' . get_class($value));
        }
    }

    /**
     * For convenience.
     *
     * @param $value
     * @return Buffer
     */
    public static function from($value = null): Buffer
    {
        return new static($value);
    }

    /**
     * For convenience.
     *
     * @param string $value
     * @return Buffer
     */
    public static function fromBase58(string $value): Buffer
    {
        $value = PublicKey::base58()->decode($value);

        return new static($value);
    }

    /**
     * @param $len
     * @param int $val
     * @return $this
     */
    public function pad($len, int $val = 0): Buffer
    {
        $this->data = array_pad($this->data, $len, $val);

        return $this;
    }

    /**
     * @param $source
     * @return $this
     */
    public function push($source): Buffer
    {
        $sourceAsBuffer = Buffer::from($source);

        array_push($this->data, ...$sourceAsBuffer->toArray());

        return $this;
    }

    /**
     * @return Buffer
     */
    public function slice(int $offset, ?int $length = null): Buffer
    {
        return static::from(array_slice($this->data, $offset, $length));
    }

    /**
     * @return ?int
     */
    public function shift(): ?int
    {
        return array_shift($this->data);
    }

    /**
     * Return binary representation of $value.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Return binary string representation of $value.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this;
    }

    /**
     * Return string representation of $value.
     *
     * @return string
     */
    public function toBase58String(): string
    {
        return PublicKey::base58()->encode($this->toString());
    }

    /**
     * @return int|void
     * @throws InputValidationException
     */
    public function count()
    {
        return sizeof($this->toArray());
    }

    /**
     * @return string
     * @throws InputValidationException
     */
    public function __toString()
    {
        return pack('C*', ...$this->toArray());
    }
}
