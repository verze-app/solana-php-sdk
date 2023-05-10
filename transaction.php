<?php
require_once __DIR__ . '/vendor/autoload.php';

use StephenHill\Base58;
use Tighten\SolanaPhpSdk\Util\Buffer;
use Tighten\SolanaPhpSdk\Programs\SystemProgram;
use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\KeyPair;
use Tighten\SolanaPhpSdk\Transaction;
use Tighten\SolanaPhpSdk\Connection;
use Tighten\SolanaPhpSdk\SolanaRpcClient;

$LAMPORTS_PER_SOL = 1000000000; //1000000000 Lamports in 1 SOL https://forums.solana.com/t/how-do-i-know-the-correct-lamport-format-for-sending-transaction-via-solana/2966

$client = new SolanaRpcClient(SolanaRpcClient::DEVNET_ENDPOINT);
$connection = new Connection($client);
$fromPublicKey = KeyPair::fromSecretKey(SecretKeyToKeypair('YourSecretKey'));
$toPublicKey = new PublicKey('Hx1snEGkiFWDtUDA1RmZA4Y3SSnbU28YySR4urCDKefv');
$instruction = SystemProgram::transfer(
    $fromPublicKey->getPublicKey(),
    $toPublicKey,
    $LAMPORTS_PER_SOL * 0.1
);

$transaction = new Transaction(null, null, $fromPublicKey->getPublicKey());
$transaction->add($instruction);

$txHash = $connection->sendTransaction($transaction, $fromPublicKey);

function GetSecretKey($keypair){
	$base58 = new base58();
	return $str = $base58->encode($keypair->getSecretKey()->toString());
}

function SecretKeyToKeypair($secretKey){
	$base58 = new base58();
	return $str = $base58->decode($secretKey);
}

?>
