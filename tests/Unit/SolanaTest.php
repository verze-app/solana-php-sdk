<?php

namespace Tighten\SolanaPhpSdk\Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mockery as M;
use Tighten\SolanaPhpSdk\Exceptions\AccountNotFoundException;
use Tighten\SolanaPhpSdk\Programs\SystemProgram;
use Tighten\SolanaPhpSdk\SolanaRpcClient;
use Tighten\SolanaPhpSdk\Tests\TestCase;

class SolanaTest extends TestCase
{
    /** @test */
    public function it_passes_undefined_calls_through_magically()
    {
        $client = new SolanaRpcClient(SolanaRpcClient::DEVNET_ENDPOINT);
        $expectedIdInHttpResponse = $client->getRandomKey();
        $solana = new SystemProgram($client);

        Http::fake([
            SolanaRpcClient::DEVNET_ENDPOINT => Http::response([
                'jsonrpc' => '2.0',
                'result' => [], // not important
                'id' => $expectedIdInHttpResponse,
            ]),
        ]);

        $solana->abcdefg([
            'param1' => 123,
        ]);

        Http::assertSent(function (Request $request) {
            return $request->url() == SolanaRpcClient::DEVNET_ENDPOINT &&
                $request['method'] == 'abcdefg' &&
                $request['params'] == ['param1' => 123];
        });
    }

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
                        'slot' => 6440
                    ],
                    'value' => null, // no account data.
                ],
                'id' => $expectedIdInHttpResponse,
            ]),
        ]);

        $this->expectException(AccountNotFoundException::class);
        $solana->getAccountInfo('abc123');
    }
}
