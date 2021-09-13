<?php

namespace Tighten\SolanaPhpSdk\Utils;

use Tighten\SolanaPhpSdk\Solana;
use Tighten\SolanaPhpSdk\SolanaRpcClient;

class Pda
{
    protected $client;

    public function __construct(SolanaRpcClient $client)
    {
        $this->client = $client;
    }

    public function getForToken(string $address)
    {
        $metaplexSeedConstant = 'metaplex';
        $publicKey = new PublicKey();

        // https://github.com/metaplex-foundation/metaplex/blob/4a1b7d2f674013bc8bd3149294c66b03b27120d0/js/packages/common/src/actions/metadata.ts#L495
        $result = $publicKey->findProgramAddress([
            $metaplexSeedConstant, // buffer from this?
            Solana::solanaTokenProgramId, // to buffer??
            $address, // to buffer?
        ], Solana::solanaTokenProgramId); // to public key?

        // 🤷‍♂️ I guess this is how we should return it? According to this:
        // // https://github.com/metaplex-foundation/metaplex/blob/4a1b7d2f674013bc8bd3149294c66b03b27120d0/js/packages/common/src/utils/utils.ts#L51-L81
        return [Base58::encode($result[0]), $result[1]];
    }
}
