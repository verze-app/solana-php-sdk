<?php

namespace Tighten\SolanaPhpSdk\Util;

use Countable;
use Tighten\SolanaPhpSdk\Exceptions\InputValidationException;
use Tighten\SolanaPhpSdk\Exceptions\TodoException;
use Tighten\SolanaPhpSdk\PublicKey;
use SplFixedArray;

/**
 * A convenient wrapper class around an array of bytes (int's).
 */
class Buffer implements Countable
{
    const FORMAT_SIGNED_CHAR = 'c';
    const FORMAT_UNSIGNED_CHAR = 'C';
    const FORMAT_SHORT_16_SIGNED = 's';
    const FORMAT_SHORT_16_UNSIGNED = 'v';
    const FORMAT_LONG_32_SIGNED = 'l';
    const FORMAT_LONG_32_UNSIGNED = 'V';
    const FORMAT_LONG_LONG_64_SIGNED = 'q';
    const FORMAT_LONG_LONG_64_UNSIGNED = 'P';

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
    public function push($source, ?int $fixedSize = null): Buffer
    {
        $sourceAsBuffer = Buffer::from($source);

        if ($fixedSize != null) {
            $sourceAsBuffer->fixed($fixedSize);
        }

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
     * @return Buffer
     */
    public function splice(int $offset, ?int $length = null): Buffer
    {
        return static::from(array_splice($this->data, $offset, $length));
    }

    /**
     * @return ?int
     */
    public function shift(): ?int
    {
        return array_shift($this->data);
    }

    /**
     * @return $this
     */
    public function fixed(int $size): Buffer
    {
        $fixedSizeData = SplFixedArray::fromArray($this->data);
        $fixedSizeData->setSize($size);
        $this->data = $fixedSizeData->toArray();

        return $this;
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

    /**
     * Convert the binary array to an int.
     *
     * Note: it is expected that the ->fixed($length) method has already been called.
     *
     * @return int
     */
    public function toInt(?int $length = null): int
    {
        if ($length) {
            $this->fixed($length);
        }

        $size = sizeof($this);

        switch ($size) {
            case 1: return $this->to(self::FORMAT_UNSIGNED_CHAR);
            case 2: return $this->to(self::FORMAT_SHORT_16_UNSIGNED);
            case 4: return $this->to(self::FORMAT_LONG_32_UNSIGNED);
            case 8: return $this->to(self::FORMAT_LONG_LONG_64_SIGNED);
            default: throw new TodoException("Large numbers that exceed PHP limits are not yet supported.");
        }
    }

    /**
     * @param $format
     * @return false|int|string
     */
    protected function to($format)
    {
        return ord(pack("{$format}*", ...$this->toArray()));
    }
}
