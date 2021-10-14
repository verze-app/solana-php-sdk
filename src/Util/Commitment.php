<?php

namespace Tighten\SolanaPhpSdk\Util;

use Tighten\SolanaPhpSdk\Exceptions\InputValidationException;

class Commitment
{
    const FINALIZED = 'finalized';
    const CONFIRMED = 'confirmed';
    const PROCESSED = 'processed';

    protected string $commitmentLevel;

    /**
     * @param string $commitmentLevel
     */
    public function __construct(string $commitmentLevel)
    {
        if (! in_array($commitmentLevel, [
            self::FINALIZED,
            self::CONFIRMED,
            self::PROCESSED,
        ])) {
            throw new InputValidationException('Invalid commitment level.');
        }

        $this->commitmentLevel = $commitmentLevel;
    }

    /**
     * @return static
     */
    public static function finalized(): Commitment
    {
        return new static(static::FINALIZED);
    }

    /**
     * @return static
     */
    public static function confirmed(): Commitment
    {
        return new static(static::CONFIRMED);
    }

    /**
     * @return static
     */
    public static function processed(): Commitment
    {
        return new static(static::PROCESSED);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->commitmentLevel;
    }
}
