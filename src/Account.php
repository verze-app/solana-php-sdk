<?php

namespace Tighten\SolanaPhpSdk;

use Tighten\SolanaPhpSdk\Util\Ed25519Keypair;

class Account
{
    protected KeyPair $keyPair;

    /**
     * @param null|string|array $secretKey
     */
    public function __construct($secretKey = null)
    {
        if ($secretKey) {
            $secretKeyString = is_string($secretKey)
                ? $secretKey
                : Ed25519Keypair::array2bin($secretKey);

            $this->keyPair = KeyPair::fromSecretKey($secretKeyString);
        } else {
            $this->keyPair = new KeyPair();
        }
    }

    /**
     * @return PublicKey
     */
    public function getPublicKey(): PublicKey
    {
        return $this->keyPair->getPublicKey();
    }

    /**
     * @return array
     */
    public function getSecretKey(): array
    {
        return $this->keyPair->getSecretKey();
    }
}
