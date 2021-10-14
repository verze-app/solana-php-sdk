<?php

namespace Tighten\SolanaPhpSdk;

use SodiumException;
use StephenHill\Base58;
use Tighten\SolanaPhpSdk\Exceptions\BaseSolanaPhpSdkException;
use Tighten\SolanaPhpSdk\Exceptions\InputValidationException;
use Tighten\SolanaPhpSdk\Util\Buffer;
use Tighten\SolanaPhpSdk\Util\HasPublicKey;

class PublicKey implements HasPublicKey
{
    const LENGTH = 32;
    const MAX_SEED_LENGTH = 32;

    /**
     * @var Buffer
     */
    protected Buffer $buffer;

    /**
     * @param array|string $bn
     */
    public function __construct($bn)
    {
        if (is_integer($bn)) {
            $this->buffer = Buffer::from()->pad(self::LENGTH, $bn);
        } elseif (is_string($bn)) {
            // https://stackoverflow.com/questions/25343508/detect-if-string-is-binary
            $isBinaryString = preg_match('~[^\x20-\x7E\t\r\n]~', $bn) > 0;

            // if not binary string already, assumed to be a base58 string.
            if ($isBinaryString) {
                $this->buffer = Buffer::from($bn);
            } else {
                $this->buffer = Buffer::fromBase58($bn);
            }

        } else {
            $this->buffer = Buffer::from($bn);
        }

        if (sizeof($this->buffer) !== self::LENGTH) {
            $len = sizeof($this->buffer);
            throw new InputValidationException("Invalid public key input. Expected length 32. Found: {$len}");
        }
    }

    /**
     * @return PublicKey
     */
    public static function default(): PublicKey
    {
        return new static('11111111111111111111111111111111');
    }

    /**
     * Check if two publicKeys are equal
     */
    public function equals($publicKey): bool
    {
        return $publicKey instanceof PublicKey && $publicKey->buffer === $this->buffer;
    }

    /**
     * Return the base-58 representation of the public key
     */
    public function toBase58(): string
    {
        return $this->base58()->encode($this->buffer->toString());
    }

    /**
     * Return the byte array representation of the public key
     */
    public function toBytes(): array
    {
        return $this->buffer->toArray();
    }

    /**
     * Return the Buffer representation of the public key
     */
    public function toBuffer(): Buffer
    {
        return $this->buffer;
    }

    /**
     * @return string
     */
    public function toBinaryString(): string
    {
        return $this->buffer;
    }

    /**
     * Return the base-58 representation of the public key
     */
    public function __toString()
    {
        return $this->toBase58();
    }

    /**
     * Derive a public key from another key, a seed, and a program ID.
     * The program ID will also serve as the owner of the public key, giving
     * it permission to write data to the account.
     *
     * @param PublicKey $fromPublicKey
     * @param string $seed
     * @param PublicKey $programId
     * @return PublicKey
     */
    public static function createWithSeed(PublicKey $fromPublicKey, string $seed, PublicKey $programId): PublicKey
    {
        $buffer = new Buffer();

        $buffer->push($fromPublicKey)
            ->push($seed)
            ->push($programId)
        ;

        $hash = hash('sha256', $buffer);
        $binaryString = sodium_hex2bin($hash);
        return new PublicKey($binaryString);
    }

    /**
     * Derive a program address from seeds and a program ID.
     *
     * @param array $seeds
     * @param PublicKey $programId
     * @return PublicKey
     */
    public static function createProgramAddress(array $seeds, PublicKey $programId): PublicKey
    {
        $buffer = new Buffer();
        foreach ($seeds as $seed) {
            $seed = Buffer::from($seed);
            if (sizeof($seed) > self::MAX_SEED_LENGTH) {
                throw new InputValidationException("Max seed length exceeded.");
            }
            $buffer->push($seed);
        }

        $buffer->push($programId)->push('ProgramDerivedAddress');

        $hash = hash('sha256', $buffer);
        $binaryString = sodium_hex2bin($hash);

        if (static::isOnCurve($binaryString)) {
            throw new InputValidationException('Invalid seeds, address must fall off the curve.');
        }

        return new PublicKey($binaryString);
    }

    /**
     * @param array $seeds
     * @param PublicKey $programId
     * @return array 2 elements, [0] = PublicKey, [1] = integer
     */
    static function findProgramAddress(array $seeds, PublicKey $programId): array
    {
        $nonce = 255;

        while ($nonce != 0) {
            try {
                $copyOfSeedsWithNonce = $seeds;
                array_push($copyOfSeedsWithNonce, [$nonce]);
                $address = static::createProgramAddress($copyOfSeedsWithNonce, $programId);
            } catch (\Exception $exception) {
                $nonce--;
                continue;
            }
            return [$address, $nonce];
        }

        throw new BaseSolanaPhpSdkException('Unable to find a viable program address nonce.');
    }

    /**
     * Check that a pubkey is on the ed25519 curve.
     */
    static function isOnCurve($publicKey): bool
    {
        try {
            $binaryString = $publicKey instanceof PublicKey
                ? $publicKey->toBinaryString()
                : $publicKey;

            $_ = sodium_crypto_sign_ed25519_pk_to_curve25519($binaryString);
            return true;
        } catch (SodiumException $exception) {
            return false;
        }
    }

    /**
     * Convenience.
     *
     * @return Base58
     */
    public static function base58(): Base58
    {
        return new Base58();
    }

    public function getPublicKey(): PublicKey
    {
        return $this;
    }
}
