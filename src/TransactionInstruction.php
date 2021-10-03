<?php

namespace Tighten\SolanaPhpSdk;

class TransactionInstruction
{
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
