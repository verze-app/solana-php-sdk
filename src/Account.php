<?php

namespace Tighten\SolanaPhpSdk;

use Tighten\SolanaPhpSdk\Util\Buffer;
use Tighten\SolanaPhpSdk\Util\HasPublicKey;
use Tighten\SolanaPhpSdk\Util\HasSecretKey;

class Account implements HasPublicKey, HasSecretKey
{
    protected Keypair $keypair;

    /**
     * @param  $secretKey
     */
    public function __construct($secretKey = null)
    {
        if ($secretKey) {
            $secretKeyString = Buffer::from($secretKey)->toString();

            $this->keypair = Keypair::fromSecretKey($secretKeyString);
        } else {
            $this->keypair = Keypair::generate();
        }
    }

    /**
     * @return PublicKey
     */
    public function getPublicKey(): PublicKey
    {
        return $this->keypair->getPublicKey();
    }

    /**
     * @return Buffer
     */
    public function getSecretKey(): Buffer
    {
        return $this->keypair->getSecretKey();
    }
}
