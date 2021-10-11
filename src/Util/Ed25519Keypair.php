<?php

namespace Tighten\SolanaPhpSdk\Util;

use SodiumException;

class Ed25519Keypair
{
    public Buffer $publicKey;
    public Buffer $secretKey;

    /**
     * @param array|string $publicKey
     * @param array|string $secretKey
     */
    public function __construct($publicKey, $secretKey)
    {
        $this->publicKey = Buffer::from($publicKey);
        $this->secretKey = Buffer::from($secretKey);
    }

    /**
     * @throws SodiumException
     */
    public static function generate(): Ed25519Keypair
    {
        return static::from(
            sodium_crypto_sign_keypair()
        );
    }

    /**
     * @param $keyPair
     * @return Ed25519Keypair
     * @throws SodiumException
     */
    public static function from($keyPair): Ed25519Keypair
    {
        return new static(
            sodium_crypto_sign_publickey($keyPair),
            sodium_crypto_sign_secretkey($keyPair)
        );
    }
}
