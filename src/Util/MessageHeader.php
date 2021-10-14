<?php

namespace Tighten\SolanaPhpSdk\Util;

class MessageHeader
{
    public int $numRequiredSignature;
    public int $numReadonlySignedAccounts;
    public int $numReadonlyUnsignedAccounts;

    public function __construct(
        int $numRequiredSignature,
        int $numReadonlySignedAccounts,
        int $numReadonlyUnsignedAccounts
    )
    {
        $this->numRequiredSignature = $numRequiredSignature;
        $this->numReadonlySignedAccounts = $numReadonlySignedAccounts;
        $this->numReadonlyUnsignedAccounts = $numReadonlyUnsignedAccounts;
    }
}
