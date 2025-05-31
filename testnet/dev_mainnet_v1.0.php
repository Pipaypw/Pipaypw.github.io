<?php


	require __DIR__ . '/vendor/autoload.php';

    use Get2\A2uphp\PiNetwork;
    use Soneso\StellarSDK\Crypto\KeyPair;
    use Soneso\StellarSDK\Crypto\StrKey;
    use Soneso\StellarSDK\SEP\Derivation\Mnemonic;
    
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;

use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TimeBounds;


    
function mint(){
	// starts with S GB6FQ4AUW7ARWTW7FCCCHFEDGFNVDTKRH4W4FZXFDDF3KILJWLNTLECI
    $api_key = "";
    $seed = "";
    $uid = "GBH7MOTJDIQQWBT4MO5ZQWHSJ7BGEXHJEUBYH45PTWAXKBE46QQMO26U";
    
    $pi = new PiNetwork($api_key, $seed);
    $incompletePayments = $pi->incompletePayments();
    if(isset($incompletePayments)){
    	echo var_dump($incompletePayments);
    }
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
                "uid" => $uid
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
}

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

function buildTx($a,$to_address,$net,$key,$app){
        $amount = $a;
        $destination = $to_address;
        $network = $net;

        $sdk = $app->getHorizonClient($network);

        ///////////////////////////////////////////////////////
        $responseFeeStats = $sdk->requestFeeStats();
        //$feeCharged = $response->getFeeCharged();
        $feeCharged = $responseFeeStats->getLastLedgerBaseFee();
        ///////////////////////////////////////////////////////

        $senderKeyPair = KeyPair::fromSeed($key);

        // Load sender account data from the stellar network.
        $sender = $sdk->requestAccount($senderKeyPair->getAccountId());

        /*$minTime = 1641803321;
        $maxTime = 1741803321;
        $timeBounds = new TimeBounds((new \DateTime)->setTimestamp($minTime), (new \DateTime)->setTimestamp($maxTime));*/
        

        $paymentOperation = (new PaymentOperationBuilder($destination,Asset::native(), $amount))->build();
        $transaction = (new TransactionBuilder($sender))
            ->addOperation($paymentOperation)
            ->setMaxOperationFee($feeCharged)
            ->addMemo(Memo::text(substr("PIPAY spenderbot v2".$destination,0,28)))
            //->setTimeBounds($timeBounds)
            ->build();
        // Sign and submit the transaction
        $transaction->sign($senderKeyPair, new Network($network));
        $response = $sdk->submitTransaction($transaction);

        if (!$response->isSuccessful()) {
            //throw new \Exception('Transaction submission failed: ' . json_encode($response->getExtras()));
            return [
                'status' => false,
                'message' => json_encode($response->getExtras())
            ];
        }

        return $response->getHash();
    }
//fromBip39SeedHex
/*
$mnemonic = Mnemonic::mnemonicFromWords("snake column market cup carry harvest outer text door east arrive script advance napkin town town virus property cabin parrot jealous enemy much occur");
$keyPair = KeyPair::fromMnemonic($mnemonic,0);

echo $keyPair->getSecretSeed();

    echo "<hr>";
echo $keyPair->getPrivateKey();
    echo "<hr>";
echo $keyPair->getPublicKey();
    echo "<hr>";
print($keyPair->getAccountId());
echo "<br>";



$tx = getAccountTxs("GDWQBIJVI2NULNS6K4WSTH5XBVDJF2VSSY23JMG4EZUPRZ2WJ25BE7UB");
foreach ($tx as $k => $v) {
	echo $k."<br>";
	echo "id ". $v->id."<br>";
	echo "hash ". $v->hash."<br>";
	echo "source_account ". $v->source_account."<br>";
	echo "source_account_sequence ". $v->source_account_sequence."<br>";
	echo "successful ". $v->successful."<br>";
	echo "paging_token ". $v->paging_token."<br>";
	echo "ledger ". $v->ledger."<br>";
	echo "fee_account π". $v->fee_account."<br>";	
	echo "operation_count ". $v->operation_count."<br>";
	echo "valid_after ". $v->valid_after."<br>";
	echo "valid_before ". $v->valid_before."<br>";
}

$cc = getAccount("GDWQBIJVI2NULNS6K4WSTH5XBVDJF2VSSY23JMG4EZUPRZ2WJ25BE7UB");
echo $cc->last_modified_ledger."<br>";
echo $cc->account_id."<br>";
echo $cc->sequence."<br>";
echo $cc->sequence_ledger."<br>";
echo $cc->sequence_time."<br>";
echo $cc->last_modified_ledger."<br>";
echo $cc->last_modified_time."<br>";
echo "π".$cc->balances[0]->balance."<br>";
echo $cc->balances[0]->buying_liabilities."<br>";
echo $cc->balances[0]->selling_liabilities."<br>";
echo $cc->balances[0]->asset_type."<br>";
echo getBalance("GDWQBIJVI2NULNS6K4WSTH5XBVDJF2VSSY23JMG4EZUPRZ2WJ25BE7UB");

*/

if(isset($_REQUEST['to'])){
$uid=$_REQUEST['to'];
}
$net='mainnet';
if(isset($_REQUEST['net'])){
$net=$_REQUEST['net'];
}

$spend=1;

if(isset($_REQUEST['spend'])){
$spend=$_REQUEST['spend'];
}

$api_key = "";
    $seed = "";
    $uid = "GBH7MOTJDIQQWBT4MO5ZQWHSJ7BGEXHJEUBYH45PTWAXKBE46QQMO26U";


       
        $pa = "89733531944632320";
      /*  $paymentData = [
            "payment" => [
                "amount" => 0.42,
                "memo" => "Refund for apple pie", // this is just an example
                "metadata" => ["productId" => "apple-pie-1"],
                "uid" => $uid
            ],
        ]; */
        
       $ntw="Pi Testnet";
        if($net=="mainnet"){
           $ntw="Pi Network";
         }
        
      
       $k= KeyPair::fromSeed($seed);
       $bal = getBalance($k->getAccountId(),$net);
             
if(isset($_REQUEST['amount']) && $_REQUEST['amount']<$bal){
$amt = $_REQUEST['amount'];
}else{
$amt=$bal;
}
       echo $net." Balance:".$bal."<br/>";
     
       echo $net." Total spending:".$amt."<br/>";
       echo "Receiver :".$uid."<br/>";
       echo "Chain :".$ntw."<br/>";
       echo "spend balance:".$spend."<hr/>";
       
          
       $pi = new PiNetwork($api_key, $seed);
       $hash = buildTx($amt,$uid,$ntw,$seed,new PiNetwork($api_key, $seed));
       if(is_array($hash)){
           echo var_dump($hash);
       }else{
       echo $k->getAccountId()." Sent ".$ntw."-Pi ".$amt." to ".$uid."<br>";
       // $identifier = $pi->createPayment($paymentData);
       
       echo "<a target='_blank' href='https://blockexplorer.minepi.com/".$net."/transactions/".$hash."'>".$hash."</a>";
       }
       
