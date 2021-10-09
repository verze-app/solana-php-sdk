<?php

namespace Tighten\SolanaPhpSdk\Util;

use SodiumException;

class Ed25519Keypair
{
    public array $publicKey;
    public array $secretKey;

    /**
     * @param array|string $publicKey
     * @param array|string $secretKey
     */
    public function __construct($publicKey, $secretKey)
    {
        $this->publicKey = is_string($publicKey)
            ? static::bin2array($publicKey)
            : $publicKey;
        $this->secretKey = is_string($secretKey)
            ? static::bin2array($secretKey)
            : $secretKey;
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

    /**
     * @param string $bin
     * @return array<integer>
     */
    public static function bin2array(string $bin): array
    {
        // for some reason, unpack return an array starting at index 1.
        return array_values(unpack('C*', $bin));
    }

    /**
     * @param array $array
     * @return string
     */
    public static function array2bin(array $array): string
    {
        return pack('C*', ...$array);
    }
}
