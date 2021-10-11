<?php

namespace Tighten\SolanaPhpSdk\Tests;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mockery as M;
use Tighten\SolanaPhpSdk\Exceptions\AccountNotFoundException;
use Tighten\SolanaPhpSdk\Solana;
use Tighten\SolanaPhpSdk\SolanaRpcClient;

class SolanaTest extends TestCase
{
    /** @test */
    public function it_passes_undefined_calls_through_magically()
    {
        $client = M::mock(SolanaRpcClient::class);
        $client->shouldReceive('call')
            ->with('abcdefg', [])
            ->times(1)
            ->andReturn($this->fakeResponse());

        $solana = new Solana($client);
        $solana->abcdefg();

        M::close();

        $this->assertTrue(true); // Keep PHPUnit from squawking; there must be a better way?
    }

    /** @test */
    public function it_will_throw_exception_when_rpc_account_response_is_null()
    {
        $client = new SolanaRpcClient(SolanaRpcClient::DEVNET_ENDPOINT);
        $expectedIdInHttpResponse = $client->getRandomKey();
        $solana = new Solana($client);
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

    protected function fakeResponse(): Response
    {
        return new Response(new class
        {
            public function getBody()
            {
                return 'i am a body yay';
            }
        });
    }
}
