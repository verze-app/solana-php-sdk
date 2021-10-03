<?php

namespace Tighten\SolanaPhpSdk;

use Tighten\SolanaPhpSdk\Exceptions\GenericException;
use Tighten\SolanaPhpSdk\Exceptions\TodoException;
use Tighten\SolanaPhpSdk\Util\NonceInformation;
use Tighten\SolanaPhpSdk\Util\SignaturePubkeyPair;

class Transaction
{
    /**
     * @var array<SignaturePubkeyPair>
     */
    protected array $signatures;
    protected ?string $recentBlockhash;
    protected ?NonceInformation $nonceInformation;
    protected ?PublicKey $feePayer;
    protected array $instructions = []; // TransactionInstruction[]

    public function __construct(
        ?string $recentBlockhash = null,
        ?NonceInformation $nonceInformation = null,
        ?PublicKey $feePayer = null,
        ?array $signatures = []
    )
    {
        $this->recentBlockhash = $recentBlockhash;
        $this->nonceInformation = $nonceInformation;
        $this->feePayer = $feePayer;
        $this->signatures = $signatures;
    }

    /**
     * The first (payer) Transaction signature
     */
    public function signature(): ?array
    {
        if (sizeof($this->signatures)) {
            return $this->signatures[0]->signature;
        }

        return null;
    }

    /**
     * @param ...$items
     * @return $this
     * @throws GenericException
     */
    public function add(...$items): Transaction
    {
        foreach ($items as $item) {
            if ($item instanceof TransactionInstruction) {
                array_push($this->instructions, $item);
            } elseif ($item instanceof Transaction) {
                array_push($this->instructions, $item->instructions);
            } else {
                throw new GenericException("Invalid parameter to add(). Only Transaction and TransactionInstruction are allows.");
            }
        }

        return $this;
    }

    /**
     * Compile transaction data
     */
    public function compileMessage(): Message
    {
        throw new TodoException('Transaction@compileMessage implementation coming soon.');
    }

    /**
     * Get a buffer of the Transaction data that need to be covered by signatures
     */
    public function serializeMessage(): array
    {
        return $this->compileMessage()->serialize();
    }
}
