<?php

namespace Tighten\SolanaPhpSdk;

use Tighten\SolanaPhpSdk\Util\Buffer;
use Tighten\SolanaPhpSdk\Util\Ed25519Keypair;
use Tighten\SolanaPhpSdk\Util\HasPublicKey;
use Tighten\SolanaPhpSdk\Util\HasSecretKey;

class Account implements HasPublicKey, HasSecretKey
{
    protected KeyPair $keyPair;

    /**
     * @param  $secretKey
     */
    public function __construct($secretKey = null)
    {
        if ($secretKey) {
            $secretKeyString = Buffer::from($secretKey)->toString();

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
    public function getSecretKey(): Buffer
    {
        return $this->keyPair->getSecretKey();
    }
}
