<?php

namespace Tighten\SolanaPhpSdk\Utils;

use Exception;

class PublicKey
{
    /**
     * Find a valid program address
     *
     * Valid program addresses must fall off the ed25519 curve.  This function
     * iterates a nonce until it finds one that when combined with the seeds
     * results in a valid program address.
     *
     * @source https://github.com/solana-labs/solana-web3.js/blob/d8c6267cb813ff7c3c8ada70b9576e1c85c6be7e/src/publickey.ts#L158-L185
     */
    public function findProgramAddress(array $seeds, $programId)
    {
        $nonce = 255;

        while ($nonce != 0) {
            try {
                $address = $this->createProgramAddress(array_merge($seeds, [$nonce]), $programId);
            } catch (Exception $e) {
                // Maybe only catch a certain type of exception? @todo
                // if (err instanceof TypeError) {
                //     throw err;
                // }

                $nonce--;
                continue;
            }

            return [$address, $nonce];
        }

        throw new Exception('Unable to find a viable program address nonce');
    }

    /**
     * Derive a program address from seeds and a program ID.
     *
     * @source https://github.com/solana-labs/solana-web3.js/blob/d8c6267cb813ff7c3c8ada70b9576e1c85c6be7e/src/publickey.ts#L131-L156
     */
    public function createProgramAddress(array $seeds, $programId)
    {
        $buffer = '';
        $maxSeedLength = '@todo find it in the js code';

        foreach ($seeds as $seed) {
            if (strlen($seed) > $maxSeedLength) {
                throw new Exception('Max seed length exceeded.');
            }

            $buffer .= $seed;
        }

        $buffer .= $programId;
        $buffer .= 'ProgramDerivedAddress';

        $hash = hash('sha256', $buffer/* @todo what the hell is uint8array */);
        $publicKeyBytes = /* @todo what the hell is a BN?!?! */' I have no idea ';

        if ($this->isOnCurve($publicKeyBytes)) {
            throw new Exception('Invalid seeds; address must fall off the curve.');
        }

        return new PublicKeyObjectIHaveNotMadeYet($publicKeyBytes);

        /*
        @todo.. try this without dying

            let publicKeyBytes = new BN(hash, 16).toArray(undefined, 32);

            if (is_on_curve(publicKeyBytes)) {
                throw new Error(`Invalid seeds, address must fall off the curve`);
            }
            return new PublicKey(publicKeyBytes);
        }
        */
    }

    public function isOnCurve($data)
    {

    }
}
