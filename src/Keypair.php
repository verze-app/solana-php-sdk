<?php

namespace Tighten\SolanaPhpSdk;

use SodiumException;
use Tighten\SolanaPhpSdk\Util\Buffer;
use Tighten\SolanaPhpSdk\Util\HasPublicKey;
use Tighten\SolanaPhpSdk\Util\HasSecretKey;

/**
 * An account keypair used for signing transactions.
 */
class Keypair implements HasPublicKey, HasSecretKey
{
    public Buffer $publicKey;
    public Buffer $secretKey;

    /**
     * @param array|string $publicKey
     * @param array|string $secretKey
     */
    public function __construct($publicKey = null, $secretKey = null)
    {
        if ($publicKey == null && $secretKey == null) {
            $keypair = sodium_crypto_sign_keypair();

            $publicKey = sodium_crypto_sign_publickey($keypair);
            $secretKey = sodium_crypto_sign_secretkey($keypair);
        }

        $this->publicKey = Buffer::from($publicKey);
        $this->secretKey = Buffer::from($secretKey);
    }

    /**
     * @return Keypair
     * @throws SodiumException
     */
    public static function generate(): Keypair
    {
        $keypair = sodium_crypto_sign_keypair();

        return static::from($keypair);
    }

    /**
     * @param string $keypair
     * @return Keypair
     * @throws SodiumException
     */
    public static function from(string $keypair): Keypair
    {
        return new static(
            sodium_crypto_sign_publickey($keypair),
            sodium_crypto_sign_secretkey($keypair)
        );
    }

    /**
     * Create a keypair from a raw secret key byte array.
     *
     * This method should only be used to recreate a keypair from a previously
     * generated secret key. Generating keypairs from a random seed should be done
     * with the {@link Keypair.fromSeed} method.
     *
     * @param $secretKey
     * @return Keypair
     */
    static public function fromSecretKey($secretKey): Keypair
    {
        $secretKey = Buffer::from($secretKey)->toString();

        $publicKey = sodium_crypto_sign_publickey_from_secretkey($secretKey);

        return new static(
            $publicKey,
            $secretKey
        );
    }

    /**
     * Generate a keypair from a 32 byte seed.
     *
     * @param string|array $seed
     * @return Keypair
     * @throws SodiumException
     */
    static public function fromSeed($seed): Keypair
    {
        $seed = Buffer::from($seed)->toString();

        $keypair = sodium_crypto_sign_seed_keypair($seed);

        return static::from($keypair);
    }

    /**
     * The public key for this keypair
     *
     * @return PublicKey
     */
    public function getPublicKey(): PublicKey
    {
        return new PublicKey($this->publicKey);
    }

    /**
     * The raw secret key for this keypair
     *
     * @return Buffer
     */
    public function getSecretKey(): Buffer
    {
        return Buffer::from($this->secretKey);
    }
}
