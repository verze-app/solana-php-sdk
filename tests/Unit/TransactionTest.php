<?php

namespace Tighten\SolanaPhpSdk\Tests\Unit;

use Tighten\SolanaPhpSdk\KeyPair;
use Tighten\SolanaPhpSdk\Message;
use Tighten\SolanaPhpSdk\Programs\SystemProgram;
use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\Tests\TestCase;
use Tighten\SolanaPhpSdk\Transaction;
use Tighten\SolanaPhpSdk\TransactionInstruction;
use Tighten\SolanaPhpSdk\Util\AccountMeta;
use Tighten\SolanaPhpSdk\Util\Buffer;
use Tighten\SolanaPhpSdk\Util\CompiledInstruction;
use Tighten\SolanaPhpSdk\Util\MessageHeader;

class TransactionTest extends TestCase
{
    /**
     * Seeded from
     * https://github.com/solana-labs/solana-web3.js/blob/master/test/transaction.test.ts
     * on October 2nd, 2021
     */
    /** @test */
    public function it_account_keys_are_ordered()
    {
        $payer = KeyPair::generate();
        $account2 = KeyPair::generate();
        $account3 = KeyPair::generate();
        $recentBlockhash = KeyPair::generate()->getPublicKey()->toBase58();
        $programId = KeyPair::generate()->getPublicKey();
        $transaction = new Transaction($recentBlockhash);
        $transaction->add(new TransactionInstruction($programId, [
            new AccountMeta($account3->getPublicKey(), true, false),
            new AccountMeta($payer->getPublicKey(), true, true),
            new AccountMeta($account2->getPublicKey(), true, true),
        ]));

        $transaction->setSigners($payer->getPublicKey(), $account2->getPublicKey(), $account3->getPublicKey());

        $message = $transaction->compileMessage();
        $this->assertEquals($payer->getPublicKey(), $message->accountKeys[0]);
        $this->assertEquals($account2->getPublicKey(), $message->accountKeys[1]);
        $this->assertEquals($account3->getPublicKey(), $message->accountKeys[2]);
    }

    /** @test */
    public function it_payer_is_first_account_meta()
    {
        $payer = KeyPair::generate();
        $other = KeyPair::generate();
        $recentBlockHash = KeyPair::generate()->getPublicKey()->toBase58();
        $programId = KeyPair::generate()->getPublicKey();
        $transaction = new Transaction($recentBlockHash);

        $transaction->add(new TransactionInstruction(
            $programId,
            [
                new AccountMeta($other->getPublicKey(), true, true),
                new AccountMeta($payer->getPublicKey(), true, true),
            ],
        ));

        $transaction->sign($payer, $other);
        $message = $transaction->compileMessage();
        $this->assertEquals($payer->getPublicKey(), $message->accountKeys[0]);
        $this->assertEquals($other->getPublicKey(), $message->accountKeys[1]);
        $this->assertEquals(2, $message->header->numRequiredSignature);
        $this->assertEquals(0, $message->header->numReadonlySignedAccounts);
        $this->assertEquals(1, $message->header->numReadonlyUnsignedAccounts);
    }

    /** @test */
    public function it_payer_is_writable()
    {
        $payer = KeyPair::generate();
        $recentBlockhash = KeyPair::generate()->getPublicKey()->toBase58();
        $programId = KeyPair::generate()->getPublicKey();
        $transaction = new Transaction($recentBlockhash);
        $transaction->add(new TransactionInstruction($programId, [
            new AccountMeta($payer->getPublicKey(), true, false)
        ]));

        $transaction->sign($payer);
        $message = $transaction->compileMessage();
        $this->assertEquals($payer->getPublicKey(), $message->accountKeys[0]);
        $this->assertEquals(1, $message->header->numRequiredSignature);
        $this->assertEquals(0, $message->header->numReadonlySignedAccounts);
        $this->assertEquals(1, $message->header->numReadonlyUnsignedAccounts);
    }

    /** @test */
    public function it_partialSign()
    {
        $account1 = KeyPair::generate();
        $account2 = KeyPair::generate();
        $recentBlockhash = $account1->getPublicKey()->toBase58(); // Fake recentBlockhash
        $transfer = SystemProgram::transfer($account1->getPublicKey(), $account2->getPublicKey(), 123);

        $partialTransaction = new Transaction($recentBlockhash);
        $partialTransaction->add($transfer);
        $partialTransaction->partialSign($account1, $account2->getPublicKey());

        $this->assertEquals(Transaction::SIGNATURE_LENGTH, strlen($partialTransaction->signature()));
        $this->assertCount(2, $partialTransaction->signatures);
        $this->assertNotNull($partialTransaction->signatures[0]->signature);
        $this->assertNull($partialTransaction->signatures[1]->signature);

        $partialTransaction->addSigner($account2);
        $this->assertNotNull($partialTransaction->signatures[0]->signature);
        $this->assertNotNull($partialTransaction->signatures[1]->signature);

        $expected = new Transaction($recentBlockhash);
        $expected->add($transfer);
        $expected->sign($account1, $account2);
        $this->assertEquals($expected, $partialTransaction);
    }

    /** @test */
    public function it_dedupe_setSigners()
    {
        $payer = KeyPair::generate();
        $duplicate1 = $payer;
        $duplicate2 = $payer;
        $recentBlockhash = KeyPair::generate()->getPublicKey()->toBase58();
        $programId = KeyPair::generate()->getPublicKey();

        $transaction = new Transaction($recentBlockhash);
        $transaction->add(new TransactionInstruction(
            $programId,
            [
                new AccountMeta($duplicate1->getPublicKey(), true, true),
                new AccountMeta($payer->getPublicKey(), false, true),
                new AccountMeta($duplicate2->getPublicKey(), true, false),
            ]
        ));

        $transaction->setSigners(
            $payer->getPublicKey(),
            $duplicate1->getPublicKey(),
            $duplicate2->getPublicKey()
        );

        $this->assertCount(1, $transaction->signatures);
        $this->assertEquals($payer->getPublicKey(), $transaction->signatures[0]->getPublicKey());

        $message = $transaction->compileMessage();
        $this->assertEquals($payer->getPublicKey(), $message->accountKeys[0]);
        $this->assertEquals(1, $message->header->numRequiredSignature);
        $this->assertEquals(0, $message->header->numReadonlySignedAccounts);
        $this->assertEquals(1, $message->header->numReadonlyUnsignedAccounts);
    }

    /** @test */
    public function it_dedupe_sign()
    {
        $payer = KeyPair::generate();
        $duplicate1 = $payer;
        $duplicate2 = $payer;
        $recentBlockhash = KeyPair::generate()->getPublicKey()->toBase58();
        $programId = KeyPair::generate()->getPublicKey();

        $transaction = new Transaction($recentBlockhash);
        $transaction->add(new TransactionInstruction(
            $programId,
            [
                new AccountMeta($duplicate1->getPublicKey(), true, true),
                new AccountMeta($payer->getPublicKey(), false, true),
                new AccountMeta($duplicate2->getPublicKey(), true, false),
            ]
        ));

        $transaction->sign(
            $payer,
            $duplicate1,
            $duplicate2
        );

        $this->assertCount(1, $transaction->signatures);
        $this->assertEquals($payer->getPublicKey(), $transaction->signatures[0]->getPublicKey());

        $message = $transaction->compileMessage();
        $this->assertEquals($payer->getPublicKey(), $message->accountKeys[0]);
        $this->assertEquals(1, $message->header->numRequiredSignature);
        $this->assertEquals(0, $message->header->numReadonlySignedAccounts);
        $this->assertEquals(1, $message->header->numReadonlyUnsignedAccounts);
    }

    /** @test */
    public function it_transfer_signatures()
    {
        $account1 = KeyPair::generate();
        $account2 = KeyPair::generate();
        $recentBlockhash = $account1->getPublicKey()->toBase58(); // Fake recentBlockhash

        $transfer1 = SystemProgram::transfer($account1->getPublicKey(), $account2->getPublicKey(), 123);
        $transfer2 = SystemProgram::transfer($account2->getPublicKey(), $account1->getPublicKey(), 123);

        $orgTransaction = new Transaction($recentBlockhash);
        $orgTransaction->add($transfer1, $transfer2);
        $orgTransaction->sign($account1, $account2);

        $newTransaction = new Transaction($orgTransaction->recentBlockhash, null, null, $orgTransaction->signatures);
        $newTransaction->add($transfer1, $transfer2);

        $this->assertEquals($orgTransaction, $newTransaction);
    }

    /** @test */
    public function it_use_nonce()
    {
        $account1 = KeyPair::generate();
        $account2 = KeyPair::generate();
        $nonceAccount = KeyPair::generate();
        $nonce = $account2->getPublicKey()->toBase58(); // Fake Nonce hash

        $this->markTestSkipped('TODO once SystemProgram::nonceAdvance is implemented.');
    }

    /** @test */
    public function it_parse_wire_format_and_serialize()
    {
        $sender = KeyPair::fromSeed([8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8]); // Arbitrary known account
        $recentBlockhash = 'EETubP5AKHgjPAhzPAFcb8BAY1hMH639CWCFTqi3hq1k'; // Arbitrary known recentBlockhash
        $recipient = new PublicKey('J3dxNj7nDRRqRRXuEMynDG57DkZK4jYRuv3Garmb1i99'); // Arbitrary known public key

        $transfer = SystemProgram::transfer($sender->getPublicKey(), $recipient, 49);
        $expectedTransaction = new Transaction($recentBlockhash, null, $sender->getPublicKey());
        $expectedTransaction->add($transfer);
        $expectedTransaction->sign($sender);

        $wireTransaction = sodium_base642bin('AVuErQHaXv0SG0/PchunfxHKt8wMRfMZzqV0tkC5qO6owYxWU2v871AoWywGoFQr4z+q/7mE8lIufNl/kxj+nQ0BAAEDE5j2LG0aRXxRumpLXz29L2n8qTIWIY3ImX5Ba9F9k8r9Q5/Mtmcn8onFxt47xKj+XdXXd3C8j/FcPu7csUrz/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAxJrndgN4IFTxep3s6kO0ROug7bEsbx0xxuDkqEvwUusBAgIAAQwCAAAAMQAAAAAAAAA=', SODIUM_BASE64_VARIANT_ORIGINAL);
        $tx = Transaction::from($wireTransaction);

        $this->assertEquals($tx, $expectedTransaction);
        $this->assertEquals($wireTransaction, $expectedTransaction->serialize());
    }

    /** @test */
    public function it_populate_transaction()
    {
        $recentBlockhash = new PublicKey(1);
        $message = new Message(
            new MessageHeader(2, 0, 3),
            [
                new PublicKey(1),
                new PublicKey(2),
                new PublicKey(3),
                new PublicKey(4),
                new PublicKey(5),
            ],
            $recentBlockhash,
            [
                new CompiledInstruction(4, [1, 2, 3], Buffer::from(array_pad([], 5, 9))),
            ],
        );

        $signatures = [
            Buffer::from(array_pad([], 64, 1))->toBase58String(),
            Buffer::from(array_pad([], 64, 2))->toBase58String(),
        ];

        $transaction = Transaction::populate($message, $signatures);
        $this->assertCount(1, $transaction->instructions);
        $this->assertCount(2, $transaction->signatures);
        $this->assertEquals($recentBlockhash, $transaction->recentBlockhash);
    }

    /** @test */
    public function it_serialize_unsigned_transaction()
    {
        $sender = KeyPair::fromSeed([8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8]); // Arbitrary known account
        $recentBlockhash = 'EETubP5AKHgjPAhzPAFcb8BAY1hMH639CWCFTqi3hq1k'; // Arbitrary known recentBlockhash
        $recipient = new PublicKey('J3dxNj7nDRRqRRXuEMynDG57DkZK4jYRuv3Garmb1i99'); // Arbitrary known public key

        $transfer = SystemProgram::transfer($sender->getPublicKey(), $recipient, 49);
        $expectedTransaction = new Transaction($recentBlockhash, null, $sender->getPublicKey());
        $expectedTransaction->add($transfer);

        $this->assertCount(0, $expectedTransaction->signatures);
        $expectedTransaction->feePayer = $sender->getPublicKey();

        // Serializing without signatures is allowed if sigverify disabled.
        $expectedTransaction->serialize(true, false); // no exception
        // Serializing the message is allowed when signature array has null signatures
        $expectedTransaction->serializeMessage(); // no exception

        $expectedTransaction->feePayer = null;
        $expectedTransaction->setSigners($sender->getPublicKey());
        $this->assertCount(1, $expectedTransaction->signatures);


        // Serializing without signatures is allowed if sigverify disabled.
        $expectedTransaction->serialize(true, false); // no exception
        // Serializing the message is allowed when signature array has null signatures
        $expectedTransaction->serializeMessage(); // no exception

        $expectedSerializationWithNoSignatures = sodium_base642bin('AQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABAAEDE5j2LG0aRXxRumpLXz29L2n8qTIWIY3ImX5Ba9F9k8r9Q5/Mtmcn8onFxt47xKj+XdXXd3C8j/FcPu7csUrz/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAxJrndgN4IFTxep3s6kO0ROug7bEsbx0xxuDkqEvwUusBAgIAAQwCAAAAMQAAAAAAAAA=', SODIUM_BASE64_VARIANT_ORIGINAL);
        $this->assertEquals($expectedSerializationWithNoSignatures, $expectedTransaction->serialize(false));

        // Properly signed transaction succeeds
        $expectedTransaction->partialSign($sender);
        $this->assertCount(1, $expectedTransaction->signatures);
        $expectedSerialization = sodium_base642bin('AVuErQHaXv0SG0/PchunfxHKt8wMRfMZzqV0tkC5qO6owYxWU2v871AoWywGoFQr4z+q/7mE8lIufNl/kxj+nQ0BAAEDE5j2LG0aRXxRumpLXz29L2n8qTIWIY3ImX5Ba9F9k8r9Q5/Mtmcn8onFxt47xKj+XdXXd3C8j/FcPu7csUrz/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAxJrndgN4IFTxep3s6kO0ROug7bEsbx0xxuDkqEvwUusBAgIAAQwCAAAAMQAAAAAAAAA=', SODIUM_BASE64_VARIANT_ORIGINAL);

        $this->assertEquals($expectedSerialization, $expectedTransaction->serialize());
        $this->assertCount(1, $expectedTransaction->signatures);
    }

    /** @test */
    public function it_externally_signed_stake_delegate()
    {
//        $authority = KeyPair::fromSeed(array_pad([], 32, 1));
//        $stake = new PublicKey(2);
//        $recentBlockhash = new PublicKey(3);
//        $vote = new PublicKey(4);

        $this->markTestSkipped('TODO once StakeProgram is implemented');
    }
}
