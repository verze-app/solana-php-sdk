<?php

namespace Tighten\SolanaPhpSdk\Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mockery as M;
use Tighten\SolanaPhpSdk\Exceptions\AccountNotFoundException;
use Tighten\SolanaPhpSdk\KeyPair;
use Tighten\SolanaPhpSdk\Programs\SystemProgram;
use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\SolanaRpcClient;
use Tighten\SolanaPhpSdk\Tests\TestCase;
use Tighten\SolanaPhpSdk\Transaction;
use Tighten\SolanaPhpSdk\TransactionInstruction;
use Tighten\SolanaPhpSdk\Util\AccountMeta;
use Tighten\SolanaPhpSdk\Util\Ed25519Keypair;
use Tighten\SolanaPhpSdk\Util\NonceInformation;
use function Sodium\add;

class TransactionTest extends TestCase
{
    /**
     * Seeded from
     * https://github.com/solana-labs/solana-web3.js/blob/master/test/transaction.test.ts
     * on October 2nd, 2021
     */

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
        $this->assertEquals($payer->getPublicKey(), $transaction->signatures[0]->publicKey);

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

//    /** @test */
//    public function it_dedupe_signatures()
//    {
//        // TODO: need SystemProgram more built out.
//    }

//    /** @test */
//    public function it_use_nonce()
//    {
//        $account1 = KeyPair::generate();
//        $account2 = KeyPair::generate();
//        $nonceAccount = KeyPair::generate();
//        $nonce = $account2->getPublicKey()->toBase58(); // Fake Nonce hash
//
////        $nonceInfo = new NonceInformation($nonce, )
//        // TODO: need SystemProgram more built out. SystemProgram::nonceAdvance().
//    }

    /** @test */
    public function it_parse_wire_format_and_serialize()
    {
        $sender = KeyPair::fromSeed(Ed25519Keypair::array2bin([8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8])); // Arbitrary known account
        $recentBlockhash = 'EETubP5AKHgjPAhzPAFcb8BAY1hMH639CWCFTqi3hq1k'; // Arbitrary known recentBlockhash
        $recipient = new PublicKey('J3dxNj7nDRRqRRXuEMynDG57DkZK4jYRuv3Garmb1i99'); // Arbitrary known public key

        $transfer = SystemProgram::transfer($sender->getPublicKey(), $recipient, 49);
        $expectedTransaction = new Transaction($recentBlockhash, null, $sender->getPublicKey());
        $expectedTransaction->add($transfer);
        $expectedTransaction->sign($sender);

        $wireTransaction = sodium_base642bin('AVuErQHaXv0SG0/PchunfxHKt8wMRfMZzqV0tkC5qO6owYxWU2v871AoWywGoFQr4z+q/7mE8lIufNl/kxj+nQ0BAAEDE5j2LG0aRXxRumpLXz29L2n8qTIWIY3ImX5Ba9F9k8r9Q5/Mtmcn8onFxt47xKj+XdXXd3C8j/FcPu7csUrz/AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAxJrndgN4IFTxep3s6kO0ROug7bEsbx0xxuDkqEvwUusBAgIAAQwCAAAAMQAAAAAAAAA=', SODIUM_BASE64_VARIANT_ORIGINAL);
        $tx = Transaction::from($wireTransaction);

        $this->assertEquals($tx, $expectedTransaction);
    }

//    /** @test */
//    public function it_populate_transaction()
//    {
//        // TODO
//    }

    /** @test */
    public function it_serialize_unsigned_transaction()
    {
        $sender = KeyPair::fromSeed(Ed25519Keypair::array2bin([8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8])); // Arbitrary known account
        $recentBlockhash = 'EETubP5AKHgjPAhzPAFcb8BAY1hMH639CWCFTqi3hq1k'; // Arbitrary known recentBlockhash
        $recipient = new PublicKey('J3dxNj7nDRRqRRXuEMynDG57DkZK4jYRuv3Garmb1i99'); // Arbitrary known public key

        $transfer = SystemProgram::transfer($sender->getPublicKey(), $recipient, 49);
        $expectedTransaction = new Transaction($recentBlockhash);
        $expectedTransaction->add($transfer);

        // Empty signature array fails.
        $this->assertCount(0, $expectedTransaction->signatures);

        $expectedTransaction->feePayer = $sender->getPublicKey();
    }
}
