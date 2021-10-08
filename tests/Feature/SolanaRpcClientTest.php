<?php

namespace Tighten\SolanaPhpSdk\Tests\Feature;

use Tighten\SolanaPhpSdk\Connection;
use Tighten\SolanaPhpSdk\KeyPair;
use Tighten\SolanaPhpSdk\Programs\SystemProgram;
use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\SolanaRpcClient;
use Tighten\SolanaPhpSdk\Tests\TestCase;
use Tighten\SolanaPhpSdk\Transaction;
use Tighten\SolanaPhpSdk\TransactionInstruction;
use Tighten\SolanaPhpSdk\Util\AccountMeta;
use Tighten\SolanaPhpSdk\Util\Ed25519Keypair;

class SolanaRpcClientTest extends TestCase
{
    /** @test */
    public function it_generates_random_key()
    {
        $client = new SolanaRpcClient('abc.com');
        $rpc1 = $client->buildRpc('doStuff', []);
        $rpc2 = $client->buildRpc('doStuff', []);

        $client = new SolanaRpcClient('abc.com');
        $rpc3= $client->buildRpc('doStuff', []);
        $rpc4 = $client->buildRpc('doStuff', []);

        $this->assertEquals($rpc1['id'], $rpc2['id']);
        $this->assertEquals($rpc3['id'], $rpc4['id']);
        $this->assertNotEquals($rpc1['id'], $rpc4['id']);
    }

    /** @test */
    public function it_validates_response_id()
    {
        // If we get back a response that doesn't have id set to this->randomKey, throw exception
        $this->markTestIncomplete();
    }

    /** @test */
    public function it_throws_exception_for_invalid_methods()
    {
        // If we get an error: invalid method response back, throw the correct exception
        $this->markTestIncomplete();
    }
//
//    /** @test */
//    public function it_test_zvv_real_transaction()
//    {
//        $client = new SolanaRpcClient(SolanaRpcClient::DEVNET_ENDPOINT);
//        $connection = new Connection($client);
//        $fromPublicKey = KeyPair::fromSecretKey(Ed25519Keypair::array2bin([60,188,247,191,72,92,196,110,33,63,144,64,224,82,162,166,206,119,196,176,59,159,71,109,232,23,210,170,245,4,23,238,18,8,56,41,50,110,56,71,191,188,111,47,82,74,157,208,189,136,210,80,237,68,198,99,71,192,214,71,111,75,220,97]));
//        $toPublicKey = new PublicKey('J3dxNj7nDRRqRRXuEMynDG57DkZK4jYRuv3Garmb1i99');
//        $instruction = SystemProgram::transfer(
//            $fromPublicKey->getPublicKey(),
//            $toPublicKey,
//            6
//        );
//
//        $transaction = new Transaction(null, null, $fromPublicKey->getPublicKey());
//        $transaction->add($instruction);
//
//        dd($connection->sendTransaction($transaction, $fromPublicKey), $fromPublicKey);
//    }
}
