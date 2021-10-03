<?php

namespace Tighten\SolanaPhpSdk;

use Illuminate\Support\Arr;
use Tighten\SolanaPhpSdk\Exceptions\GenericException;
use Tighten\SolanaPhpSdk\Exceptions\TodoException;
use Tuupola\Base58;

class PublicKey
{
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
     * Checks if two publicKeys are equal
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
     */
    public static function createWithSeed(PublicKey $fromPublicKey, string $seed, PublicKey $programId): PublicKey
    {
        throw new TodoException("PublicKey@createWithSeed implementation is coming soon!");
    }

    /**
     * Derive a program address from seeds and a program ID.
     */
    public static function createProgramAddress(array $seeds, PublicKey $programId): PublicKey
    {
        throw new TodoException("PublicKey@createProgramAddress implementation is coming soon!");
    }

    /**
     * Check that a pubkey is on the ed25519 curve.
     */
    static function isOnCurve($publicKey): bool
    {
        throw new TodoException("PublicKey@createProgramAddress implementation is coming soon!");
    }

    /**
     * Convenience.
     *
     * @return Base58
     */
    protected function base58(): Base58
    {
        return new Base58(["characters" => Base58::BITCOIN]);
    }
}
