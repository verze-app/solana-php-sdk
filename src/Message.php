<?php

namespace Tighten\SolanaPhpSdk;

use Tighten\SolanaPhpSdk\Exceptions\GenericException;
use Tighten\SolanaPhpSdk\Exceptions\TodoException;
use Tighten\SolanaPhpSdk\Util\CompiledInstruction;
use Tighten\SolanaPhpSdk\Util\Ed25519Keypair;
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
            return !$this->isProgramId($index);
        });
    }

    /**
     * @return string
     * @throws TodoException
     */
    public function serialize(): string
    {
        $out = [
            ...$this->encodeMessage(),
            ...ShortVec::encodeLength(sizeof($this->instructions)),
        ];

        foreach ($this->instructions as $instruction) {
            array_push($out, ...$this->encodeInstruction($instruction));
        }

        return Ed25519Keypair::array2bin($out);
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
            ...Ed25519Keypair::bin2array(PublicKey::base58()->decode($this->recentBlockhash)),
        ];
    }

    protected function encodeInstruction(CompiledInstruction $instruction): array
    {
        $data = Ed25519Keypair::bin2array(PublicKey::base58()->decode($instruction->data));

        $accounts = $instruction->accounts;;

        return [
            // uint8
            ...unpack("C*", pack("C", $instruction->programIdIndex)),

            ...ShortVec::encodeLength(sizeof($accounts)),
            ...$accounts,

            ...ShortVec::encodeLength(sizeof($data)),
            ...$data
        ];
    }

    /**
     * @param array $rawMessage
     * @return Message
     */
    public static function from(array $rawMessage): Message
    {
        $HEADER_OFFSET = 3;
        if (sizeof($rawMessage) < $HEADER_OFFSET) {
            throw new GenericException('byte representation of message is missing message header');
        }

        $numRequiredSignatures = array_shift($rawMessage); //$rawMessage[0];
        $numReadonlySignedAccounts = array_shift($rawMessage); //$rawMessage[1];
        $numReadonlyUnsignedAccounts = array_shift($rawMessage); //$rawMessage[2];
        $header = new MessageHeader($numRequiredSignatures, $numReadonlySignedAccounts, $numReadonlyUnsignedAccounts);

        $accountKeys = [];
        list($accountsLength, $accountsOffset) = ShortVec::decodeLength($rawMessage);
        for ($i = 0; $i < $accountsLength; $i++) {
            $keyBytes = array_slice($rawMessage, $accountsOffset, PublicKey::LENGTH);
            array_push($accountKeys, (new PublicKey($keyBytes))->toBase58());
            $accountsOffset += PublicKey::LENGTH;
        }
        $rawMessage = array_slice($rawMessage, $accountsOffset);

        $recentBlockhash = PublicKey::base58()->encode(Ed25519Keypair::array2bin(array_slice($rawMessage, 0, PublicKey::LENGTH)));
        $rawMessage = array_slice($rawMessage, PublicKey::LENGTH);

        $instructions = [];
        list($instructionCount, $offset) = ShortVec::decodeLength($rawMessage);
        $rawMessage = array_slice($rawMessage, $offset);
        for ($i = 0; $i < $instructionCount; $i++) {
            $programIdIndex = array_shift($rawMessage); // $rawMessage[0];

            list ($accountsLength, $offset) = ShortVec::decodeLength($rawMessage);
            $rawMessage = array_slice($rawMessage, $offset);
            $accounts = array_slice($rawMessage, 0, $accountsLength);
            $rawMessage = array_slice($rawMessage, $accountsLength);

            list ($dataLength, $offset) = ShortVec::decodeLength($rawMessage);
            $rawMessage = array_slice($rawMessage, $offset);
            $data = array_slice($rawMessage, 0, $dataLength);
            $rawMessage = array_slice($rawMessage, $dataLength);

            array_push($instructions, new CompiledInstruction($programIdIndex, $accounts, Ed25519Keypair::array2bin($data)));
        }

        return new Message(
            $header,
            $accountKeys,
            $recentBlockhash,
            $instructions
        );
    }
}
