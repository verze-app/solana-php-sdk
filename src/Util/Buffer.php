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
    const TYPE_STRING = 'string';
    const TYPE_BYTE = 'byte';
    const TYPE_SHORT = 'short';
    const TYPE_INT = 'int';
    const TYPE_LONG = 'long';
    const TYPE_FLOAT = 'float';

    const FORMAT_CHAR_SIGNED = 'c';
    const FORMAT_CHAR_UNSIGNED = 'C';
    const FORMAT_SHORT_16_SIGNED = 's';
    const FORMAT_SHORT_16_UNSIGNED = 'v';
    const FORMAT_LONG_32_SIGNED = 'l';
    const FORMAT_LONG_32_UNSIGNED = 'V';
    const FORMAT_LONG_LONG_64_SIGNED = 'q';
    const FORMAT_LONG_LONG_64_UNSIGNED = 'P';
    const FORMAT_FLOAT = 'e';

    /**
     * @var array<int>
     */
    protected array $data;

    /**
     * @var bool is this a signed or unsigned value?
     */
    protected ?bool $signed = null;

    /**
     * @var ?string $datatype
     */
    protected ?string $datatype = null;

    /**
     * @param mixed $value
     */
    public function __construct($value = null, ?string $datatype = null, ?bool $signed = null)
    {
        $this->datatype = $datatype;
        $this->signed = $signed;

        $isString = is_string($value);
        $isNumeric = is_numeric($value);

        if ($isString || $isNumeric) {
            $this->datatype = $datatype;
            $this->signed = $signed;

            // unpack returns an array indexed at 1.
            $this->data = $isString
                ? array_values(unpack("C*", $value))
                : array_values(unpack("C*", pack($this->computedFormat(), $value)));
        } elseif (is_array($value)) {
            $this->data = $value;
        } elseif ($value instanceof PublicKey) {
            $this->data = $value->toBytes();
        } elseif ($value instanceof Buffer) {
            $this->data = $value->toArray();
            $this->datatype = $value->datatype;
            $this->signed = $value->signed;
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
    public static function from($value = null, ?string $format = null, ?bool $signed = null): Buffer
    {
        return new static($value, $format, $signed);
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
    public function slice(int $offset, ?int $length = null, ?string $format = null, ?bool $signed = null): Buffer
    {
        return static::from(array_slice($this->data, $offset, $length), $format, $signed);
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
     * Convert the binary array to its corresponding value derived from $datatype, $signed, and sizeof($data).
     *
     * Note: it is expected that the ->fixed($length) method has already been called.
     *
     * @return mixed
     */
    public function value(?int $length = null)
    {
        if ($length) {
            $this->fixed($length);
        }

        if ($this->datatype === self::TYPE_STRING) {
            return ord(pack("C*", ...$this->toArray()));
        } else {
            return unpack($this->computedFormat(), pack("C*", ...$this->toArray()))[1];
        }
    }

    /**
     * @return string
     * @throws InputValidationException
     */
    protected function computedFormat()
    {
        if (! $this->datatype) {
            throw new InputValidationException('Trying to calculate format of unspecified buffer. Please specify a datatype.');
        }

        switch ($this->datatype) {
            case self::TYPE_STRING: return self::FORMAT_CHAR_UNSIGNED;
            case self::TYPE_BYTE: return $this->signed ? self::FORMAT_CHAR_SIGNED : self::FORMAT_CHAR_UNSIGNED;
            case self::TYPE_SHORT: return $this->signed ? self::FORMAT_SHORT_16_SIGNED : self::FORMAT_SHORT_16_UNSIGNED;
            case self::TYPE_INT: return $this->signed ? self::FORMAT_LONG_32_SIGNED : self::FORMAT_LONG_32_UNSIGNED;
            case self::TYPE_LONG: return $this->signed ? self::FORMAT_LONG_LONG_64_SIGNED : self::FORMAT_LONG_LONG_64_UNSIGNED;
            case self::TYPE_FLOAT: return self::FORMAT_FLOAT;
            default: throw new InputValidationException("Unsupported datatype.");
        }
    }

}
