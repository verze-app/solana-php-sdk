<?php

namespace Tighten\SolanaPhpSdk;

use Tighten\SolanaPhpSdk\Exceptions\TodoException;
use Tighten\SolanaPhpSdk\Util\CompiledInstruction;
use Tighten\SolanaPhpSdk\Util\MessageHeader;

class Message
{
    protected MessageHeader $header;
    protected array $accountKeys;
    protected string $recentBlockhash;
    protected array $instructions;

    /**
     * int to PublicKey: https://github.com/solana-labs/solana-web3.js/blob/966d7c653198de193f607cdfe19a161420408df2/src/message.ts
     *
     * @var array
     */
    private array $indexToProgramIds;

    /**
     * @param MessageHeader $header
     * @param array $accountKeys
     * @param string $recentBlockhash
     * @param array $instructions
     */
    public function __construct(
        MessageHeader $header,
        array         $accountKeys, // string[]
        string        $recentBlockhash,
        array         $instructions // CompiledInstruction[]
    )
    {
        $this->header = $header;
        $this->accountKeys = array_map(function (string $accountKey) {
            return new KeyPair($accountKey);
        }, $accountKeys);
        $this->recentBlockhash = $recentBlockhash;
        $this->instructions = $instructions;

        $this->indexToProgramIds = [];
        /**
         * @var CompiledInstruction $instruction
         */
        foreach ($instructions as $i => $instruction) {
            $this->indexToProgramIds[$instruction->programIdIndex] = $this->accountKeys[$instruction->programIdIndex];
        }
    }

    /**
     * @param int $index
     * @return bool
     */
    public function isAccountSigner(int $index): bool
    {
        return $index < $this->header->numRequiredSignature;
    }

    /**
     * @param int $index
     * @return bool
     */
    public function isAccountWritable(int $index): bool
    {
        return $index < ($this->header->numRequiredSignature - $this->header->numReadonlySignedAccounts)
            || ($index >= $this->header->numRequiredSignature && $index < sizeof($this->accountKeys) - $this->header->numReadonlyUnsignedAccounts);
    }

    /**
     * @param int $index
     * @return bool
     */
    public function isProgramId(int $index): bool
    {
        return array_key_exists($index, $this->indexToProgramIds);
    }

    /**
     * @return array<PublicKey>
     */
    public function programIds(): array
    {
        return array_values($this->indexToProgramIds);
    }

    /**
     * @return array
     */
    public function nonProgramIds(): array
    {
        return array_filter($this->accountKeys, function (PublicKey $account, $index) {
            return !$this->isProgramId($index);
        });
    }

    /**
     * @return array
     * @throws TodoException
     */
    public function serialize(): array
    {
        throw new TodoException('Message@serialize is coming soon.');
    }

    /**
     * @param array $buffer
     * @return Message
     * @throws TodoException
     */
    public static function from(array $buffer): Message
    {
        throw new TodoException('Message@from is coming soon.');
    }
}
