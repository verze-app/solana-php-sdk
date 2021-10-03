<?php

namespace Tighten\SolanaPhpSdk\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mockery as M;
use Tighten\SolanaPhpSdk\Exceptions\AccountNotFoundException;
use Tighten\SolanaPhpSdk\Programs\SystemProgram;
use Tighten\SolanaPhpSdk\SolanaRpcClient;

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
//        $payer = Key
    }
}
