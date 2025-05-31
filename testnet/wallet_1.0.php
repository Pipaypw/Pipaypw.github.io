<?php 
require __DIR__ . '/vendor/autoload.php';
use Get2\A2uphp\PiNetwork;

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Claimant;
use Soneso\StellarSDK\ClaimClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\CreateClaimableBalanceOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Responses\Effects\ClaimableBalanceCreatedEffectResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;
use Soneso\StellarSDK\TimeBounds;

// This file handles wallet functions like address generation and getting account(address) information from the pi network 

/* 
To generate an address we need seed, the private key where the address is being generated from.
For a wallet to generate an address, it needs a Mnemonic phrase (let's call it $phrase) to create an account which has a lot of address index on it.

And KeyPair is a crypto class object that contains the both the public and the private key.

Standard Address generation flow.
convert $phrase to KeyPair instance (let's call it $keyPair) or get the $keyPair object using only private key (let's call it $seed)

We can use the KeyPair function instance $keyPair to $keyPair->getAddressId() returns the public key wallet address as(string)

or get the $seed from $keyPair->getSeed();

Pi wallet displays the address on the index 0 of derivationPath on default .

Since each address has its own private key pair, we can hold elot of address using this file to get more KeyPair from indexes on the Mnemonic phrase 

To sign a transaction on the pi network the KeyPair(object) of an address is needed 


// Functions

// convert a KeyPair of an index of a $mnemonic phrase (index 0 if index is undefined)
function phraseToKeyPair($phrase,$index=0){
  $mnemonic = Mnemonic::mnemonicFromWords($phrase);
  return KeyPair::fromMnemonic($mnemonic,$index);
}


// Mnemonic word phrase 
$phrase = "please clump young chair crack earn among chase wrap abandon donkey rebuild seminar idle hungry excess brick unfold phone vacant cross brief gadget august ";



// Let's run an example 
$keyPair = phraseToKeyPair($phrase);
$seed = $keyPair->getSecretSeed();
$address = $keyPair->getAccountId();

printf("address: %s <br>seed: %s", $address, $seed);*/


// convert phrase to a KeyPair of a mnemonic phrase index(index 0 if index is undefined)
function phraseToKeyPair($phrase,$index=0){
    
  $mnemonic = Mnemonic::mnemonicFromWords($phrase);
  return KeyPair::fromMnemonic($mnemonic,$index);
}

// convert $seed to $keyPair
function seedToKeyPair($seed){
  return KeyPair::fromSeed($seed);
}
// convert $phrase to address on index
function phraseToAddress($phrase,$index=0){
  $keyPair = phraseToKeyPair($phrase,$index);
  return $keyPair->getAccountId();
}
// convert $seed to address (we don't need index because a seed comes from an index on a Mnemonic phrase 
function seedToAddress($seed){
  $keyPair = seedToKeyPair($seed);
  return $keyPair->getAccountId();
}

function getAccount($addr,$n='mainnet'){
	$acc = file_get_contents("https://api.".$n.".minepi.com/accounts/{$addr}");
	return json_decode($acc);
}

/* gets the balance of an address 
  @param address 
  @param network enum(mainnet,testnet)
 */
function getBalance($addr,$n='mainnet'){
	$acc = getAccount($addr,$n);
	return $acc->balances[0]->balance;
}
// get a json->object of transactions of an address 
function getAccountTxs($addr,$n='mainnet'){
    $acc = file_get_contents("https://api.".$n.".minepi.com/accounts/{$addr}/transactions");
    $b= json_decode($acc);
    return $b->_embedded->records;
}

function getAccountOps($addr,$n='mainnet'){
    $acc = file_get_contents("https://api.".$n.".minepi.com/accounts/{$addr}/operations");
    // id,source_account,transaction_successful,type,type_i,created_at,transaction_hash,starting_balance,funder,account) (asset_type,from,to,amount)  
    $b= json_decode($acc);
    return $b->_embedded->records;
    // types of ops (0,)(1,payment(asset_type,from,to,amount))
}


  
// Mnemonic word phrase 
$phrase = "please clump young chair crack earn among chase wrap abandon donkey rebuild seminar idle hungry excess brick unfold phone vacant cross brief gadget august";


// Let's run an example 
$network = "testnet";


$keyPair = phraseToKeyPair($phrase);
$seed = $keyPair->getSecretSeed();
$address = $keyPair->getAccountId();



printf("address: %s <br>seed: %s <br> balance: %s", $address, $seed, getBalance($address,$network));
