<?php

namespace Tighten\SolanaPhpSdk\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Tighten\SolanaPhpSdk\Exceptions\GenericException;
use Tighten\SolanaPhpSdk\Programs\SystemProgram;
use Tighten\SolanaPhpSdk\SolanaRpcClient;
use Tighten\SolanaPhpSdk\Tests\TestCase;

class SolanaTest extends TestCase
{
    /** @test */
    public function it_will_throw_exception_when_rpc_account_response_is_null()
    {
        $client = new SolanaRpcClient(SolanaRpcClient::DEVNET_ENDPOINT);
        $expectedIdInHttpResponse = $client->getRandomKey();
        $solana = new SystemProgram($client);
        Http::fake([
            SolanaRpcClient::DEVNET_ENDPOINT => Http::response([
                'jsonrpc' => '2.0',
                'result' => [
                    'context' =>  [
                        'slot' => 6440,
                    ],
                    'value' => null, // no account data.
                ],
                'id' => $expectedIdInHttpResponse,
            ]),
        ]);

        $this->expectException(GenericException::class);
        $solana->getAccountInfo('abc123');
    }
}
