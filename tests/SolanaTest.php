<?php

namespace Tighten\SolanaPhpSdk\Tests;

use Illuminate\Http\Client\Response;
use Mockery as M;
use PHPUnit\Framework\TestCase;
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
