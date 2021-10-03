<?php

namespace Tighten\SolanaPhpSdk;

use Tighten\SolanaPhpSdk\Exceptions\GenericException;
use Tighten\SolanaPhpSdk\Exceptions\TodoException;
use Tighten\SolanaPhpSdk\Util\AccountMeta;
use Tighten\SolanaPhpSdk\Util\CompiledInstruction;
use Tighten\SolanaPhpSdk\Util\Ed25519Keypair;
use Tighten\SolanaPhpSdk\Util\MessageHeader;
use Tighten\SolanaPhpSdk\Util\NonceInformation;
use Tighten\SolanaPhpSdk\Util\SignaturePubkeyPair;
use Tighten\SolanaPhpSdk\Util\Signer;

class Transaction
{
    /**
     * @var array<SignaturePubkeyPair>
     */
    protected array $signatures;
    protected ?string $recentBlockhash;
    protected ?NonceInformation $nonceInformation;
    protected ?PublicKey $feePayer;
    /**
     * @var array<TransactionInstruction>
     */
    protected array $instructions = [];

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
        $nonceInfo = $this->nonceInformation;

        if ($nonceInfo && sizeof($this->instructions) && $this->instructions[0] != $nonceInfo->nonceInstruction) {
            $this->recentBlockhash = $nonceInfo->nonce;
            array_unshift($this->instructions, $nonceInfo->nonceInstruction);
        }

        $recentBlockhash = $this->recentBlockhash;
        if (! $recentBlockhash) {
            throw new GenericException('Transaction recentBlockhash required');
        }

        if ($this->feePayer) {
            $feePayer = $this->feePayer;
        } elseif (sizeof($this->signatures) && $this->signatures[0]->publicKey) {
            $feePayer = $this->signatures[0]->publicKey;
        } else {
            throw new GenericException('Transaction fee payer required');
        }

        foreach ($this->instructions as $i => $instruction) {
            if (! $instruction->programId) {
                throw new GenericException("Transaction instruction index {$i} has undefined program id");
            }
        }

        /**
         * @var array<string> $programIds
         */
        $programIds = [];
        /**
         * @var array<AccountMeta> $accountMetas
         */
        $accountMetas = [];

        foreach ($this->instructions as $instruction) {
            array_push($accountMetas, ...$instruction->keys);

            $programId = $instruction->programId->toBase58();
            if (! in_array($programId, $programIds)) {
                array_push($programIds, $programId);
            }
        }

        // Append programID account metas
        foreach ($programIds as $programId) {
            array_push($accountMetas, new AccountMeta(
                new PublicKey($programId),
                false,
                false
            ));
        }

        // Sort. Prioritizing first by signer, then by writable
        usort($accountMetas, function (AccountMeta $x, AccountMeta $y) {
            if ($x->isSigner !== $y->isSigner) {
                return $x->isSigner ? -1 : 1;
            }

            if ($x->isWritable !== $y->isWritable) {
                return $x->isWritable ? -1 : 1;
            }

            return 0;
        });

        // Cull duplicate account metas
        /**
         * @var array<AccountMeta> $uniqueMetas
         */
        $uniqueMetas = [];
        foreach ($accountMetas as $accountMeta) {
            $eachPublicKey = $accountMeta->publicKey;
            $uniqueIndex = $this->arraySearchAccountMetaForPublicKey($uniqueMetas, $eachPublicKey);

            if ($uniqueIndex > -1) {
                $uniqueMetas[$uniqueIndex]->isWritable = $uniqueMetas[$uniqueIndex]->isWritable || $accountMeta->isWritable;
            } else {
                array_push($uniqueMetas, $accountMeta);
            }
        }

        // Move fee payer to the front
        $feePayerIndex = $this->arraySearchAccountMetaForPublicKey($uniqueMetas, $feePayer);
        if ($feePayerIndex > -1) {
            list($payerMeta) = array_splice($uniqueMetas, $feePayerIndex, 1);
            $payerMeta->isSigner = true;
            $payerMeta->isWritable = true;
            array_unshift($uniqueMetas, $payerMeta);
        } else {
            array_unshift($uniqueMetas, new AccountMeta($feePayer, true, true));
        }

        // Disallow unknown signers
        foreach ($this->signatures as $signature) {
            $uniqueIndex = $this->arraySearchAccountMetaForPublicKey($uniqueMetas, $signature);
            if ($uniqueIndex > -1) {
                $uniqueMetas[$uniqueIndex]->isSigner = true;
            } else {
                throw new GenericException("unknown signer: {$signature->publicKey->toBase58()}");
            }
        }

        $numRequiredSignatures = 0;
        $numReadonlySignedAccounts = 0;
        $numReadonlyUnsignedAccounts = 0;

        // Split out signing from non-signing keys and count header values
        /**
         * @var array<string> $signedKeys
         */
        $signedKeys = [];
        /**
         * @var array<string> $unsignedKeys
         */
        $unsignedKeys = [];

        foreach ($uniqueMetas as $accountMeta) {
            if ($accountMeta->isSigner) {
                array_push($signedKeys, $accountMeta->publicKey->toBase58());
                $numRequiredSignatures++;
                if (! $accountMeta->isWritable) {
                    $numReadonlySignedAccounts++;
                }
            } else {
                array_push($unsignedKeys, $accountMeta->publicKey->toBase58());
                if (! $accountMeta->isWritable) {
                    $numReadonlyUnsignedAccounts++;
                }
            }
        }

        $accountKeys = array_merge($signedKeys, $unsignedKeys);
        /**
         * @var array<CompiledInstruction> $instructions
         */
        $instructions = array_map(function (TransactionInstruction $instruction) use ($accountKeys) {
            $programIdIndex = array_search($instruction->programId->toBase58(), $accountKeys);
            $encodedData = PublicKey::base58()->encode(Ed25519Keypair::array2bin($instruction->data));
            $accounts = array_map(function (AccountMeta $meta) use ($accountKeys) {
                return array_search($meta->publicKey->toBase58(), $accountKeys);
            }, $instruction->keys);
            return new CompiledInstruction(
                $programIdIndex,
                $accounts,
                $encodedData
            );
        }, $this->instructions);

        return new Message(
            new MessageHeader(
                $numRequiredSignatures,
                $numReadonlySignedAccounts,
                $numReadonlyUnsignedAccounts
            ),
            $accountKeys,
            $recentBlockhash,
            $instructions
        );
    }

    /**
     * @return Message
     */
    protected function compile(): Message
    {
        $message = $this->compileMessage();
        $signedKeys = array_slice($message->accountKeys, 0, $message->header->numRequiredSignature);

        if (sizeof($this->signatures) === sizeof($signedKeys)
            && $this->signatures == $signedKeys) { // todo does this simple comparison work for comparing complex arrays?
            return $message;
        }

        $this->signatures = array_map(function (PublicKey $publicKey) {
            return new SignaturePubkeyPair($publicKey, null);
        }, $signedKeys);

        return $message;
    }

    /**
     * Get a buffer of the Transaction data that need to be covered by signatures
     */
    public function serializeMessage(): string
    {
        return $this->compile()->serialize();
    }

    /**
     * Sign the Transaction with the specified signers. Multiple signatures may
     * be applied to a Transaction. The first signature is considered "primary"
     * and is used identify and confirm transactions.
     *
     * If the Transaction `feePayer` is not set, the first signer will be used
     * as the transaction fee payer account.
     *
     * Transaction fields should not be modified after the first call to `sign`,
     * as doing so may invalidate the signature and cause the Transaction to be
     * rejected.
     *
     * The Transaction must be assigned a valid `recentBlockhash` before invoking this method
     *
     * @param array<Signer> $signers
     */
    public function sign(...$signers)
    {
        // Dedupe signers
        $uniqueSigners = $this->arrayUnique($signers);

        $this->signatures = array_map(function ($signer) {
            return new SignaturePubkeyPair($this->toPublicKey($signer), null);
        }, $signers);

        $message = $this->compile();
        $this->_partialSign($message, ...$uniqueSigners);
        $this->_verifySignature($message->serialize(), true);
    }

    /**
     * Partially sign a transaction with the specified accounts. All accounts must
     * correspond to either the fee payer or a signer account in the transaction
     * instructions.
     *
     * All the caveats from the `sign` method apply to `partialSign`
     *
     * @param array<Signer> $signers
     */
    public function partialSign(...$signers)
    {
        // Dedupe signers
        $uniqueSigners = $this->arrayUnique($signers);

        $message = $this->compile();
        $this->_partialSign($message, ...$uniqueSigners);
    }

    /**
     * @param Message $message
     * @param array<Signer> $signers
     */
    protected function _partialSign(Message $message, ...$signers)
    {
        $signData = $message->serialize();
        foreach ($signers as $signer) {
            $signature = sodium_crypto_sign_detached($signData, $signer->secretKey);
            $this->_addSignature($signer->publicKey, $signature);
        }
    }

    /**
     * Add an externally created signature to a transaction. The public key
     * must correspond to either the fee payer or a signer account in the transaction
     * instructions.
     *
     * @param PublicKey $publicKey
     * @param string $signature
     * @throws GenericException
     */
    public function addSignature(PublicKey $publicKey, string $signature)
    {
        $this->compile(); // Ensure signatures array is populated
        $this->_addSignature($publicKey, $signature);
    }

    /**
     * @param PublicKey $publicKey
     * @param string $signature
     */
    protected function _addSignature(PublicKey $publicKey, string $signature)
    {
        $indexOfPublicKey = $this->arraySearchAccountMetaForPublicKey($this->signatures, $publicKey);

        if ($indexOfPublicKey === -1) {
            throw new GenericException("unknown signer: {$publicKey->toBase58()}");
        }

        $this->signatures[$indexOfPublicKey]->signature = $signature;
    }

    /**
     * @return bool
     */
    public function verifySignatures(): bool
    {
        return $this->_verifySignature($this->serializeMessage(), true);
    }

    /**
     * @param string $signData
     * @param bool $requireAllSignatures
     * @return bool
     */
    protected function _verifySignature(string $signData, bool $requireAllSignatures): bool
    {
        foreach ($this->signatures as $signature) {
            if (! $signature->signature) {
                if ($requireAllSignatures) {
                    return false;
                }
            } else {
                if (! sodium_crypto_sign_verify_detached($signData, $signature->signature, $signature->publicKey->toBinaryString())) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Serialize the Transaction in the wire format.
     *
     * @param bool|null $requireAllSignature
     * @param bool|null $verifySignatures
     */
    public function serialize(bool $requireAllSignature = true, bool $verifySignatures = true)
    {
        $signData = $this->serializeMessage();

        if ($verifySignatures && ! $this->_verifySignature($signData, $requireAllSignature)) {
            throw new GenericException('Signature verification failed');
        }

        return $this->_serialize($signData);
    }

    /**
     * @param string $signData
     * @return string
     * @throws TodoException
     */
    protected function _serialize(string $signData): string
    {
        throw new TodoException('TODO: implement Transaction@serialize');
    }

    /**
     * @param array<AccountMeta> $haystack
     * @param PublicKey|SignaturePubkeyPair|AccountMeta|string $needle
     * @return int|string
     */
    protected function arraySearchAccountMetaForPublicKey(array $haystack, $needle)
    {
        $publicKeyToSearchFor = $this->toPublicKey($needle);

        foreach ($haystack as $i => $item)
        {
            if ($this->toPublicKey($item) == $publicKeyToSearchFor) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * @param array $haystack
     * @return array
     * @throws GenericException
     */
    protected function arrayUnique(array $haystack)
    {
        $unique = [];
        foreach ($haystack as $item) {
            $indexOfSigner = $this->arraySearchAccountMetaForPublicKey($unique, $item);

            if ($indexOfSigner === -1) {
                array_push($unique, $item);
            }
        }

        return $unique;
    }

    /**
     * @param $source
     * @return PublicKey
     * @throws GenericException
     */
    protected function toPublicKey($source): PublicKey
    {
        if ($source instanceof PublicKey) {
            return $source;
        } elseif ($source instanceof SignaturePubkeyPair) {
            return $source->publicKey;
        } elseif ($source instanceof AccountMeta) {
            return $source->publicKey;
        } elseif (is_string($source)) {
            return new PublicKey($source);
        } elseif ($source instanceof KeyPair) {
            return $source->getPublicKey();
        } else {
            throw new GenericException('Invalid $needle input into arraySearchAccountMetaForPublicKey');
        }
    }
}
