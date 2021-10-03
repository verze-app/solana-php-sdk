<?php

namespace Tighten\SolanaPhpSdk\Util;

class CompiledInstruction
{
    public int $programIdIndex;
    /**
     * array of indexes.
     *
     * @var array<integer>
     */
    public array $accounts;
    public string $data;

    public function __construct(
        int $programIdIndex,
        array $accounts,
        string $data
    )
    {
        $this->programIdIndex = $programIdIndex;
        $this->accounts = $accounts;
        $this->data = $data;
    }
}
