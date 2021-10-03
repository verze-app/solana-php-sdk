<?php

namespace Tighten\SolanaPhpSdk\Util;

class CompiledInstruction
{
    public int $programIdIndex;
    public array $accounts;
    public string $data;

    public function __construct(
        int $programIdIndex,
        array $accounts, // number[]
        string $data
    )
    {
        $this->programIdIndex = $programIdIndex;
        $this->accounts = $accounts;
        $this->data = $data;
    }
}
