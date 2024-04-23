<?php

namespace Tighten\SolanaPhpSdk\Programs;

use Tighten\SolanaPhpSdk\Program;
use Tighten\SolanaPhpSdk\Accounts\DidData;
use Tighten\SolanaPhpSdk\PublicKey;
use StephenHill\Base58;
use Tighten\SolanaPhpSdk\SolanaRpcClient;

/**
 * Class DidSolProgram
 * 
 * This class represents a program for interacting with the Solana blockchain using the DID (Decentralized Identifier) protocol.
 * It provides methods for creating and managing DID accounts, signing and verifying messages, and other related operations.
 * @version 1.0
 * @package Tighten\SolanaPhpSdk\
 * @license MIT
 * @author Eduardo Chongkan
 * @link https://chongkan.com
 * @see https://github.com/identity-com/sol-did
 */

class DidSolProgram extends Program
{
    public const DIDSOL_PROGRAM_ID = 'didso1Dpqpm4CsiCjzP766BGY89CAdD6ZBL68cRhFPc';
    public const DIDSOL_DEFAULT_SEED = 'did-account';

    /**
     * getDidDataAcccountInfo
     *
     * @param SolanaRpcClient $client
     * @param string $base58SubjectPk The PK of the DID.
     * @param bool $onlyAccData
     * @return DidData|string
     * @example DidSolProgram::getDidDataAcccountInfo($client, 'did:sol:3Js7k6xYQbvXv6qUYLapYV7Sptfg37Tss9GcAyVEuUqk', false);
     */
    static function getDidDataAcccountInfo($client, $base58SubjectPk, $onlyAccData = true)
    {
        $pdaPublicKey =  self::getDidDataAccountId($base58SubjectPk);

        $accountInfoResponse = $client->call('getAccountInfo', [$pdaPublicKey, ["encoding" => "jsonParsed"]]);
        $dataBase64 = $accountInfoResponse['value']['data'][0];

        if (!$onlyAccData) {
            return $dataBase64;
        }


        $didData = self::deserializeDidData($dataBase64);

        return $didData;
    }


    /**
     * getDidDataAccountId
     *
     * @param string $did 'did:sol:[cluster]....'
     * @return string The base58 encoded public key of the DID data account
     * @example DidSolProgram::getDidDataAccountId('did:sol:devnet:3Js7k6xYQbvXv6qUYLapYV7Sptfg37Tss9GcAyVEuUqk');
     */
    static function getDidDataAccountId($base58SubjectPk)
    {

        $b58 = new Base58();

        $seeds = array(self::DIDSOL_DEFAULT_SEED, $b58->decode($base58SubjectPk));
        $pId = new PublicKey(self::DIDSOL_PROGRAM_ID);
        $publicKey =  PublicKey::findProgramAddress($seeds, $pId);

        return $publicKey[0]->toBase58();
    }

    /**
     * deserializeDidData
     *
     * @param string $dataBase64 The base64 encoded data of the DID data account
     * @return DidData The deserialized DID data object
     * @example DidSolProgram::deserializeDidData('TVjvjfsd7fMA/gAAAA...');
     */
    static function deserializeDidData($dataBase64)
    {

        $base64String = base64_decode($dataBase64);
        $uint8Array = array_values(unpack('C*', $base64String));
        $didData = DidData::fromBuffer($uint8Array);

        $keyData = $didData->keyData;

        $binaryString = pack('C*', ...$keyData);

        $b58 = new Base58();
        $base58String = $b58->encode($binaryString);
        $didData->keyData = $base58String;
        return $didData;
    }
}
