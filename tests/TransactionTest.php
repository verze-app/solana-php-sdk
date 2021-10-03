<?php

namespace Tighten\SolanaPhpSdk\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mockery as M;
use Tighten\SolanaPhpSdk\Exceptions\AccountNotFoundException;
use Tighten\SolanaPhpSdk\KeyPair;
use Tighten\SolanaPhpSdk\Programs\SystemProgram;
use Tighten\SolanaPhpSdk\SolanaRpcClient;
use Tighten\SolanaPhpSdk\Transaction;
use Tighten\SolanaPhpSdk\TransactionInstruction;
use Tighten\SolanaPhpSdk\Util\AccountMeta;
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
}
