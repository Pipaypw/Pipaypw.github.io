<?php
require __DIR__ . '/vendor/autoload.php';
include("spenderbot.php");

use Pipaypw\Payroll\SpenderBot;

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


$network = "Pi Network";
$api_key = "upvolovagkrgjsiaqrfz5enghwnw4fcubn2z47d2u3nlt6xwpzxhmnk3tvjlrwba";
$seed ="SCDXVWUNITAJAGXGV6VEWPLZCDV54RHAR4CN3VZJW2M7K32YP54PKW76";
$to = "GBLIRXYKBLRHJDXU5726O2L7U4I2VC3U4566GKZXHLWQNSFPYUMZUJYI";
$sA = KeyPair::fromSeed($seed);
$pi = new SpenderBot($network, $api_key, $seed, $to);
$sdk = $pi->getHorizonClient($network);

function getAccount($addr,$n='mainnet'){
	$acc = file_get_contents("https://api.".$n.".minepi.com/accounts/{$addr}");
	return json_decode($acc);
}
function getBalance($addr,$n='mainnet'){
	$acc = getAccount($addr,$n);
	return $acc->balances[0]->balance;
}
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

  function claims($adr,$sdk){
    $requestBuilder = $sdk->claimableBalances()->forClaimant($adr);
    $response = $requestBuilder->execute();
    $items = $response->getClaimableBalances()->count() ;

    $cb = $response->getClaimableBalances()->toArray()[$items-1];
    return $cb;
  }
  
  // $sA->getAccountId();
  
  // getAmount,balanceId,humanReadableEffectType, account,createdAt,
  //echo $sw->spend('1000');
  //var_dump($sw->incompletePayments());
  //echo $sw->getFeeRate()."<br>";
  //echo $sw->getOwner();
  
  /* 
  $clm = claims($sA->getAccountId(),$sdk);
  echo $clm->getSponsor()."<BR>";
  echo $clm->getAmount()."<BR>";
  echo $clm->getBalanceId()."<BR>";
  echo $clm->getLastModifiedTime()."<BR>";
  */
  $uid ="76009728454426913";
  $incompletePayments = $pi->incompletePayments();
    $identifier = "";
    $cancelAllIncompletePayments = true;
    if ($cancelAllIncompletePayments) {
        $pi->cancelAllIncompletePayments();
    }elseif(is_array($incompletePayments) && isset($incompletePayments[0])){
        $identifier = $incompletePayments[0]->identifier;
    }
    if($cancelAllIncompletePayments){
        $paymentData = [
            "payment" => [
                "amount" => 0.42,
                "memo" => "Refund for apple pie", // this is just an example
                "metadata" => ["productId" => "apple-pie-1"],
                "uid" => $pi->getDestination()
            ],
        ];
        $identifier = $pi->createPayment($paymentData);
    }
    if (isset($identifier['status']) && $identifier['status']===false) {
        die($identifier['message']);
    }
    $payment = $pi->getPayment($identifier);
    echo "success";echo nl2br("\n");
    var_dump($payment);echo nl2br("\n");echo nl2br("\n");

    $txid = "";
    try {
        $submitResponse = $pi->submitPayment($identifier);
        echo nl2br("\n");
        var_dump($submitResponse);
        $txid = $submitResponse;
    } catch (\Exception $e) {
        $data = json_decode($e->getMessage());
        $txid = $data->txid;
    }
    if (isset($txid['status']) && $txid['status']===false) {
        die($txid['message']);
    }
    echo nl2br("\n");echo nl2br("\n");
    echo "Payment completion";echo nl2br("\n");
    $paymentComplete = $pi->completePayment($identifier, $txid);
    var_dump($paymentComplete);