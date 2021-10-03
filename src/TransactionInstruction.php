<?php

namespace Tighten\SolanaPhpSdk;

use Tighten\SolanaPhpSdk\Util\AccountMeta;

class TransactionInstruction
{
    /**
     * @var array<AccountMeta>
     */
    public array $keys;
    public PublicKey $programId;
    public array $data;

    public function __construct(PublicKey $programId, array $keys, array $data = [])
    {
        $this->programId = $programId;
        $this->keys = $keys;
        $this->data = $data;
    }
}
