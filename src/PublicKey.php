<?php

namespace Tighten\SolanaPhpSdk;

use StephenHill\Base58;
use Tighten\SolanaPhpSdk\Exceptions\GenericException;
use Tighten\SolanaPhpSdk\Util\Ed25519Keypair;
use SodiumException;

class PublicKey
{
    const LENGTH = 32;
    const MAX_SEED_LENGTH = 32;

    /**
     * @var array|false
     */
    protected array $byteArray;

    /**
     * @param array|string $bn
     */
    public function __construct($bn)
    {
        if (is_string($bn)) {
            $bn = $this->base58()->decode($bn);
            $this->byteArray = array_values(unpack('C*', $bn)); // unpack index starts at 1. array_values to get it to 0.
        } elseif (is_array($bn)) {
            $this->byteArray = $bn;
        } elseif ($bn instanceof PublicKey) {
            $this->byteArray = $bn->byteArray;
        } else {
            throw new GenericException("Invalid PublicKey input");
        }

        if (sizeof($this->byteArray) !== 32) {
            $len = sizeof($this->byteArray);
            throw new GenericException("Invalid public key input. Expected length 32. Found: {$len}");
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
        return $publicKey instanceof PublicKey && $publicKey->byteArray === $this->byteArray;
    }

    /**
     * Return the base-58 representation of the public key
     */
    public function toBase58(): string
    {
        $base58String = pack('C*', ...$this->byteArray);
        return $this->base58()->encode($base58String);
    }

    /**
     * Return the byte array representation of the public key
     */
    public function toBytes(): array
    {
        return $this->toBuffer();
    }

    /**
     * Return the Buffer representation of the public key
     */
    public function toBuffer(): array
    {
        return array_pad($this->byteArray, 32, 0);
    }

    /**
     * @return string
     */
    public function toBinaryString(): string
    {
        return Ed25519Keypair::array2bin($this->byteArray);
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
        $buffer = [];
        array_push($buffer,
            ...$fromPublicKey->toBytes(),
            ...Ed25519Keypair::bin2array($seed),
            ...$programId->toBytes()
        );

        $hash = hash('sha256', Ed25519Keypair::array2bin($buffer));
        $binaryString = sodium_hex2bin($hash);
        return new PublicKey(Ed25519Keypair::bin2array($binaryString));
    }

    /**
     * Derive a program address from seeds and a program ID.
     *
     * @param array<array<integer>> $seeds
     * @param PublicKey $programId
     * @return PublicKey
     */
    public static function createProgramAddress(array $seeds, PublicKey $programId): PublicKey
    {
        $buffer = [];
        foreach ($seeds as $seed) {
            if (sizeof($seed) > self::MAX_SEED_LENGTH) {
                throw new GenericException("Max seed length exceeded");
            }
            array_push($buffer, ...$seed);
        }

        array_push($buffer,
            ...$programId->toBytes(),
            ...Ed25519Keypair::bin2array('ProgramDerivedAddress')
        );

        $hash = hash('sha256', Ed25519Keypair::array2bin($buffer));
        $binaryString = sodium_hex2bin($hash);

        if (static::isOnCurve($binaryString)) {
            throw new GenericException('Invalid seeds, address must fall off the curve');
        }

        return new PublicKey(Ed25519Keypair::bin2array($binaryString));
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

        throw new GenericException('Unable to find a viable program address nonce');
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
}
