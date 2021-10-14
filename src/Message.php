<?php

namespace Tighten\SolanaPhpSdk;

use Tighten\SolanaPhpSdk\Exceptions\InputValidationException;
use Tighten\SolanaPhpSdk\Util\Buffer;
use Tighten\SolanaPhpSdk\Util\CompiledInstruction;
use Tighten\SolanaPhpSdk\Util\MessageHeader;
use Tighten\SolanaPhpSdk\Util\ShortVec;

class Message
{
    public MessageHeader $header;
    /**
     * @var array<PublicKey>
     */
    public array $accountKeys;
    public string $recentBlockhash;
    /**
     * @var array<CompiledInstruction>
     */
    public array $instructions;

    /**
     * int to PublicKey: https://github.com/solana-labs/solana-web3.js/blob/966d7c653198de193f607cdfe19a161420408df2/src/message.ts
     *
     * @var array
     */
    private array $indexToProgramIds;

    /**
     * @param MessageHeader $header
     * @param array<string> $accountKeys
     * @param string $recentBlockhash
     * @param array<CompiledInstruction> $instructions
     */
    public function __construct(
        MessageHeader $header,
        array $accountKeys,
        string $recentBlockhash,
        array $instructions
    )
    {
        $this->header = $header;
        $this->accountKeys = array_map(function (string $accountKey) {
            return new PublicKey($accountKey);
        }, $accountKeys);
        $this->recentBlockhash = $recentBlockhash;
        $this->instructions = $instructions;

        $this->indexToProgramIds = [];

        foreach ($instructions as $instruction) {
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
            return ! $this->isProgramId($index);
        });
    }

    /**
     * @return string
     */
    public function serialize(): string
    {
        $out = new Buffer();

        $out->push($this->encodeMessage())
            ->push(ShortVec::encodeLength(sizeof($this->instructions)))
        ;

        foreach ($this->instructions as $instruction) {
            $out->push($this->encodeInstruction($instruction));
        }

        return $out;
    }

    /**
     * @return array
     */
    protected function encodeMessage(): array
    {
        $publicKeys = [];

        foreach ($this->accountKeys as $publicKey) {
            array_push($publicKeys, ...$publicKey->toBytes());
        }

        return [
            // uint8
            ...unpack("C*", pack("C", $this->header->numRequiredSignature)),
            // uint8
            ...unpack("C*", pack("C", $this->header->numReadonlySignedAccounts)),
            // uint8
            ...unpack("C*", pack("C", $this->header->numReadonlyUnsignedAccounts)),

            ...ShortVec::encodeLength(sizeof($this->accountKeys)),
            ...$publicKeys,
            ...Buffer::fromBase58($this->recentBlockhash)->toArray(),
        ];
    }

    protected function encodeInstruction(CompiledInstruction $instruction): array
    {
        $data = $instruction->data;

        $accounts = $instruction->accounts;;

        return [
            // uint8
            ...unpack("C*", pack("C", $instruction->programIdIndex)),

            ...ShortVec::encodeLength(sizeof($accounts)),
            ...$accounts,

            ...ShortVec::encodeLength(sizeof($data)),
            ...$data->toArray(),
        ];
    }

    /**
     * @param array|Buffer $rawMessage
     * @return Message
     */
    public static function from($rawMessage): Message
    {
        $rawMessage = Buffer::from($rawMessage);

        $HEADER_OFFSET = 3;
        if (sizeof($rawMessage) < $HEADER_OFFSET) {
            throw new InputValidationException('Byte representation of message is missing message header.');
        }

        $numRequiredSignatures = $rawMessage->shift();
        $numReadonlySignedAccounts = $rawMessage->shift();
        $numReadonlyUnsignedAccounts = $rawMessage->shift();
        $header = new MessageHeader($numRequiredSignatures, $numReadonlySignedAccounts, $numReadonlyUnsignedAccounts);

        $accountKeys = [];
        list($accountsLength, $accountsOffset) = ShortVec::decodeLength($rawMessage);
        for ($i = 0; $i < $accountsLength; $i++) {
            $keyBytes = $rawMessage->slice($accountsOffset, PublicKey::LENGTH);
            array_push($accountKeys, (new PublicKey($keyBytes))->toBase58());
            $accountsOffset += PublicKey::LENGTH;
        }
        $rawMessage = $rawMessage->slice($accountsOffset);

        $recentBlockhash = $rawMessage->slice(0, PublicKey::LENGTH)->toBase58String();
        $rawMessage = $rawMessage->slice(PublicKey::LENGTH);

        $instructions = [];
        list($instructionCount, $offset) = ShortVec::decodeLength($rawMessage);
        $rawMessage = $rawMessage->slice($offset);
        for ($i = 0; $i < $instructionCount; $i++) {
            $programIdIndex = $rawMessage->shift();

            list ($accountsLength, $offset) = ShortVec::decodeLength($rawMessage);
            $rawMessage = $rawMessage->slice($offset);
            $accounts = $rawMessage->slice(0, $accountsLength)->toArray();
            $rawMessage = $rawMessage->slice($accountsLength);

            list ($dataLength, $offset) = ShortVec::decodeLength($rawMessage);
            $rawMessage = $rawMessage->slice($offset);
            $data = $rawMessage->slice(0, $dataLength);
            $rawMessage = $rawMessage->slice($dataLength);

            array_push($instructions, new CompiledInstruction($programIdIndex, $accounts, $data));
        }

        return new Message(
            $header,
            $accountKeys,
            $recentBlockhash,
            $instructions
        );
    }
}
