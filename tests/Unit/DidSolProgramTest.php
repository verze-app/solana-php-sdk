<?php

namespace Tighten\SolanaPhpSdk\Tests\Unit;

use Tighten\SolanaPhpSdk\Account;
use Tighten\SolanaPhpSdk\Keypair;
use Tighten\SolanaPhpSdk\Tests\TestCase;
use Tighten\SolanaPhpSdk\Programs\DidSolProgram;
use Tighten\SolanaPhpSdk\SolanaRpcClient;

class DidSolProgramTest extends TestCase
{

    public const ACC_DATA_SIZE = 158;
    public const DID_ID = 'did:sol:devnet:3Js7k6xYQbvXv6qUYLapYV7Sptfg37Tss9GcAyVEuUqk';
    public const DID_SUBJECT_PK = '3Js7k6xYQbvXv6qUYLapYV7Sptfg37Tss9GcAyVEuUqk';
    public const DID_ACCOUNT_ID = '2LA5JTs1cxFewfnXzVBpaFHpABBj1akR2aQzwDSovwCg';
    public const DID_DATA = 'TVjvjfsd7fMA/gAAAAAAAAAABwAAAGRlZmF1bHRIAAAgAAAAIkrqC+g88eamANb3tU6OiBJW21IjBWP85MhI4XKkOscAAAAAAQAAAAUAAABhZ2VudAwAAABBZ2VudFNlcnZpY2UtAAAAaHR0cHM6Ly9hdHRlc3R0by1icmVlemUtdnVlLnRlc3QvLndlbGwta25vd24vAAAAAAAAAAA=';
    /** @test */
    public function it_deserializes_diddata()
    {
        $base64Data = self::DID_DATA;
        $didData = DidSolProgram::deserializeDidData($base64Data);

        $this->assertEquals($didData->keyData, self::DID_SUBJECT_PK);
       
    }
    /** @test */
    public function it_gets_did_data_account_info()
    {
        $client = new SolanaRpcClient(SolanaRpcClient::DEVNET_ENDPOINT);
        $accountInfoResponse = DidSolProgram::getDidDataAcccountInfo($client, self::DID_SUBJECT_PK, false);
        $this->assertEquals($accountInfoResponse, self::DID_DATA);
     
    }
    /** @test */
    public function it_gets_did_data_account_info_data()
    {
        $client = new SolanaRpcClient(SolanaRpcClient::DEVNET_ENDPOINT);
        $didData = DidSolProgram::getDidDataAcccountInfo($client, self::DID_SUBJECT_PK,);
        $this->assertEquals($didData->keyData, self::DID_SUBJECT_PK);
     
    }
    /** @test */
    public function it_gets_did_data_account_id()
    {
       
        $didId = DidSolProgram::getDidDataAccountId( self::DID_SUBJECT_PK,);
        $this->assertEquals($didId, self::DID_ACCOUNT_ID);
     
    }
}
