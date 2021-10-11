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
    public Buffer $data;

    public function __construct(
        int $programIdIndex,
        array $accounts,
        $data
    )
    {
        $this->programIdIndex = $programIdIndex;
        $this->accounts = $accounts;
        $this->data = Buffer::from($data);
    }
}
