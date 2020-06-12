<?php
//tx spider, crawl the latest transactions and save to db.
include "config.php";

while(1){
FullTxsSpider();
echo "Re-cheecking...\n";
sleep(60);
}

function FullTxsSpider(){	
	for($i=GetTopHeight()-10;$i<GetTopHeight();$i++){
		$height=$i;
		$url=DATA_SRC_SITE."v2/key-blocks/height/$height";
		$websrc=getwebsrc($url);
		$info=json_decode($websrc);		
		$prev_hash=$info->prev_hash;
		$prev_key_hash=$info->prev_key_hash;
		if($prev_hash!=$prev_key_hash){ProcessMicroBlock($prev_hash);}
		echo "Height $height....checked.\n";
		//sleep(1);
	}	
}

function ProcessMicroBlock($microhash){	
	ProcessTransactions($microhash);
	$url=DATA_SRC_SITE."v2/micro-blocks/hash/$microhash/header";
	$websrc=getwebsrc($url);		
	$info=json_decode($websrc);
	$height=$info->height;
	$prev_hash=$info->prev_hash;
	$prev_key_hash=$info->prev_key_hash;
	$hash=$microhash;

	if($prev_hash!=$prev_key_hash){ProcessMicroBlock($prev_hash);}
		
	}
	

function ProcessTransactions($hash){
        $url=DATA_SRC_SITE."v2/micro-blocks/hash/$hash/transactions";
        $utc=getUTC($hash);
        //echo " $url\n";
        $ttl=0;
        $websrc=getwebsrc($url);
        if(strpos($websrc,"hash")==false){echo $websrc ;return 0;}
        $info=json_decode($websrc);
        $txcounter=count($info->transactions);	
        for($m=0;$m<$txcounter;$m++){
                $type=$info->transactions[$m]->tx->type;
                //print_r($info->transactions[$m]);
                //sleep(200);
                $txhash=$info->transactions[$m]->hash;
                $block_hash=$info->transactions[$m]->block_hash;    
                $block_height=$info->transactions[$m]->block_height;  
                $fee=$info->transactions[$m]->tx->fee;                
                
                $sql="SELECT txhash from tx WHERE txhash='$txhash'";
                $conn_string = "host=aeknow.db port=5432 dbname=postgres password=".DB_PASS." user=".DB_USER;
                $db1 = pg_connect($conn_string);
                $result_query1 = pg_query($db1, $sql);

                if ((pg_num_rows($result_query1) == 0)) {
						$tx=json_encode($info->transactions[$m]);
                        $sql_insert="INSERT INTO tx(txtype,txhash,block_height,utc,block_hash,fee) VALUES('$type','$txhash',$block_height,$utc,'$block_hash',$fee)";
                     
                        
                        $isinsert=true;
                        if($type=="SpendTx"){
							//if(notscam($info->transactions[$m]->tx->sender_id)){
								$sender_id=$info->transactions[$m]->tx->sender_id;
								$recipient_id=$info->transactions[$m]->tx->recipient_id;
								$amount=$info->transactions[$m]->tx->amount;
								
								$sql_insert="INSERT INTO tx(txtype,txhash,sender_id,recipient_id,amount,block_height,utc,block_hash,fee) VALUES('$type','$txhash','$sender_id','$recipient_id',$amount,$block_height,$utc,'$block_hash',$fee)";
								//checkAccountDB($sender_id);checkAccountDB($recipient_id);
							//}else{
							//	  $isinsert=false;;
							//	}
							}
                        
                         if($type=="OracleRegisterTx" || $type=="NameRevokeTx" ||$type=="NamePreclaimTx"||$type=="NameUpdateTx"){
							$sender_id=$info->transactions[$m]->tx->account_id;
							$sql_insert="INSERT INTO tx(txtype,txhash,sender_id,block_height,utc,block_hash,fee) VALUES('$type','$txhash','$sender_id',$block_height,$utc,'$block_hash',$fee)";
							}
                        
                         if($type=="NameTransferTx"){
							$sender_id=$info->transactions[$m]->tx->account_id;
							$recipient_id=$info->transactions[$m]->tx->recipient_id;
							$sql_insert="INSERT INTO tx(txtype,txhash,sender_id,recipient_id,block_height,utc,block_hash,fee) VALUES('$type','$txhash','$sender_id','$recipient_id',$block_height,$utc,'$block_hash',$fee)";
							}
                        
                         if($type=="NameClaimTx"){
							$sender_id=$info->transactions[$m]->tx->account_id;
							$recipient_id=strtolower($info->transactions[$m]->tx->name);
							$sql_insert="INSERT INTO tx(txtype,txhash,sender_id,recipient_id,block_height,utc,block_hash,fee) VALUES('$type','$txhash','$sender_id','$recipient_id',$block_height,$utc,'$block_hash',$fee)";
							}
                        
                        if($type=="OracleQueryTx"){
							$sender_id=$info->transactions[$m]->tx->sender_id;
							$recipient_id=str_replace("ok_","ak_",$info->transactions[$m]->tx->oracle_id);
							$sql_insert="INSERT INTO tx(txtype,txhash,sender_id,recipient_id,block_height,utc,block_hash,fee) VALUES('$type','$txhash','$sender_id','$recipient_id',$block_height,$utc,'$block_hash',$fee)";
							}
							
                        if($type=="OracleResponseTx" ||$type=="OracleExtendTx" ){
							$sender_id=str_replace("ok_","ak_",$info->transactions[$m]->tx->oracle_id);
							$sql_insert="INSERT INTO tx(txtype,txhash,sender_id,block_height,utc,block_hash,fee) VALUES('$type','$txhash','$sender_id',$block_height,$utc,'$block_hash',$fee)";
							}
                        
                        if($type=="ContractCreateTx"){
							$sender_id=$info->transactions[$m]->tx->owner_id;
							$sql_insert="INSERT INTO tx(txtype,txhash,sender_id,block_height,utc,block_hash,fee) VALUES('$type','$txhash','$sender_id',$block_height,$utc,'$block_hash',$fee)";
							}
                        
                        if($type=="ContractCallTx"){
							$sender_id=$info->transactions[$m]->tx->caller_id;
							$recipient_id=$info->transactions[$m]->tx->contract_id;
							$call_data=$info->transactions[$m]->tx->call_data;
							
							$sql_insert="INSERT INTO tx(txtype,txhash,sender_id,contract_id,block_height,utc,block_hash,fee) VALUES('$type','$txhash','$sender_id','$recipient_id',$block_height,$utc,'$block_hash',$fee)";
							
							//CheckContracts($caller_id,$contract_id,$call_data,$block_height)
							
							}
                        
                        if($type=="ChannelCreateTx"){
							$sender_id=$info->transactions[$m]->tx->initiator_id;
							$recipient_id=$info->transactions[$m]->tx->responder_id;
							$sql_insert="INSERT INTO tx(txtype,txhash,sender_id,recipient_id,block_height,utc,block_hash,fee) VALUES('$type','$txhash','$sender_id','$recipient_id',$block_height,$utc,'$block_hash',$fee)";
							}
                        
                        if($type=="ChannelDepositTx" || $type=="ChannelForceProgressTx"|| $type=="ChannelCloseSoloTx" || $type=="ChannelCloseMutualTx" || $type=="ChannelSettleTx" ){
							$sender_id=$info->transactions[$m]->tx->from_id;
							$sql_insert="INSERT INTO tx(txtype,txhash,sender_id,block_height,utc,block_hash,fee) VALUES('$type','$txhash','$sender_id',$block_height,$utc,'$block_hash',$fee)";
							}
                        
                        if($type=="ChannelWithdrawTx"){
							$recipient_id=$info->transactions[$m]->tx->to_id;
							$sql_insert="INSERT INTO tx(txtype,txhash,recipient_id,block_height,utc,block_hash,fee) VALUES('$type','$txhash','$recipient_id',$block_height,$utc,'$block_hash',$fee)";
							}
                        
                        
                        
                        //echo "$sql_insert\n";sleep(2);
                        
                        if($isinsert){
							$result_insert = pg_query($db1, $sql_insert);
						  //  echo "$sql_insert\n";sleep(20);
							echo "$type $txhash inerted.\n";
							
							if($type=="ContractCallTx"){
								CheckContracts($sender_id,$recipient_id,$call_data,$block_height,$txhash);
								}
								
                        }else{
							echo "$type $txhash scam.\n";
							}
							
							
                        }//else{echo "$type $txhash in DB.\n";}

                }
}
function getUTC($block_hash){
	$url=DATA_SRC_SITE.'v2/micro-blocks/hash/'.$block_hash.'/header';
	$websrc=getwebsrc($url);		
		if(strpos($websrc,"prev_hash")>0){
			$info=json_decode($websrc);
			return $info->time;
			}
	return "0000";
	}
	


	
function checkAccountDB($address){
        $conn_string = "host=aeknow.db port=5432 dbname=postgres password=".DB_PASS." user=".DB_USER;
        $db2 = pg_connect($conn_string);
        $sql = "SELECT * FROM accountsinfo WHERE address='$address'";
        $result_query = pg_query($db2, $sql);
        if (pg_num_rows($result_query) == 0) {
                $sql_insert="INSERT INTO accountsinfo(address) VALUES('$address')";
                        $result_query1 = pg_query($db2, $sql_insert);echo ".";
                }
        
        $ak=$address;
        $url=DATA_SRC_SITE."v2/accounts/$ak";
		$websrc=getwebsrc($url);
		
		$balance=0;
		if(strpos($websrc,"balance")==TRUE){
			   $info=json_decode($websrc);
			   $balance=$info->balance;
			   $readtime=time();
			$sql_update="UPDATE accountsinfo SET balance=$balance,readtime=$readtime WHERE address='$ak'";
			$result_insert = pg_query($db2, $sql_update);echo "U";
		}
		
}


function CheckContracts($caller_id,$contract_id,$call_data,$block_height,$txhash){
	$conn_string = "host=aeknow.db port=5432 dbname=postgres password=".DB_PASS." user=".DB_USER;
	$db = pg_connect($conn_string);
	$sql = "SELECT ctype,alias,decimal FROM contracts_token WHERE address='$contract_id'";
	//echo "$sql\n";sleep(1);
	$result_query = pg_query($db, $sql);
	
	if (pg_num_rows($result_query) == 0) {		
		$url=DATA_SRC_SITE."v2/contracts/$contract_id";
		$websrc=getwebsrc($url);
		if(strpos($websrc,"owner_id")==TRUE){
			$info=json_decode($websrc);
			$owner_id=$info->owner_id;
			$sql_insert="INSERT INTO contracts_token(address,owner_id,lastcall) VALUES('$contract_id','$owner_id',$block_height)";
			$result_query1 = pg_query($db, $sql_insert);echo "I";
		}
		}else{
			$sql_update="UPDATE contracts_token SET calltime=calltime+1,lastcall=$block_height WHERE address='$contract_id'";	
			$result_update = pg_query($db, $sql_update);echo "U";
			
			while ($row= pg_fetch_row($result_query)) {
				$ctype=$row[0];
				$alias=$row[1];
				$decimal=$row[2];
				}
				
			if($ctype=="AEX9"){
				$data=decode_token_transfer($call_data,$decimal);
				//print_r($data);sleep(1);
					if($data['amount']>0){
						ImportDB($caller_id,$contract_id,$alias,$decimal);
						ImportDB_update($data['address'],$contract_id,$alias,$decimal,$txhash);						
					}
				}
			}
	}

function ImportDB_update($account,$contract,$alias,$decimal,$txhash){
	$sql="SELECT balance from token WHERE account='$account' AND contract='$contract'";
	$conn_string = "host=aeknow.db port=5432 dbname=postgres password=".DB_PASS." user=".DB_USER;
	$db1 = pg_connect($conn_string);
	$result_query1 = pg_query($db1, $sql);
	
	$balance=getBalance($account,$contract);

	if ((pg_num_rows($result_query1) == 0)) {		
		if($balance>0){
			$sql_insert="INSERT INTO token(account,balance,alias,decimal,contract) VALUES('$account',$balance,'$alias',$decimal,'$contract')";
			//echo "$sql_insert\n";sleep(100);
			$result_insert = pg_query($db1, $sql_insert);
			if($result_insert){
				echo $sql_insert."\n";
				}
		}
		}else{
			$balance_db=0;
			while ($row= pg_fetch_row($result_query1)) {
				$balance_db=$row[0];
				}
			
			if(($balance>0) && ($balance!=$balance_db)){
				$sql_update="UPDATE token SET balance=$balance WHERE account='$account' AND contract='$contract'";
				$result_update = pg_query($db1, $sql_update);
				if(!$result_update){
					echo $sql_update."...failed.\n";
					}else{echo $sql_update."...OK.\n";}					
				}else{
					echo "$account =>$balance ...exists.\n";
					}
			
			//update recipient_id
			$sql_update="UPDATE tx SET recipient_id='$account' WHERE txhash='$txhash'";
			$result_update = pg_query($db1, $sql_update);
			
			if(!$result_update){
				echo $sql_update."...failed.\n";
				}else{echo "$txhash =>$account ...updated.\n";}	
				
				
			//TODO update 
			}
	}

function ImportDB($account,$contract,$alias,$decimal){
	$sql="SELECT balance from token WHERE account='$account' AND contract='$contract'";
	$conn_string = "host=aeknow.db port=5432 dbname=postgres password=".DB_PASS." user=".DB_USER;
	$db1 = pg_connect($conn_string);
	$result_query1 = pg_query($db1, $sql);
	
	$balance=getBalance($account,$contract);

	if ((pg_num_rows($result_query1) == 0)) {		
		if($balance>0){
			$sql_insert="INSERT INTO token(account,balance,alias,decimal,contract) VALUES('$account',$balance,'$alias',$decimal,'$contract')";
			//echo "$sql_insert\n";sleep(100);
			$result_insert = pg_query($db1, $sql_insert);
			if($result_insert){
				echo $sql_insert."\n";
				}
		}
		}else{
			$balance_db=0;
			while ($row= pg_fetch_row($result_query1)) {
				$balance_db=$row[0];
				}
			
			if(($balance>0) && ($balance!=$balance_db)){
				$sql_update="UPDATE token SET balance=$balance WHERE account='$account' AND contract='$contract'";
				$result_update = pg_query($db1, $sql_update);
				if(!$result_update){
					echo $sql_update."...failed.\n";
					}else{echo $sql_update."...OK.\n";sleep(2);}
				}else{
					echo "$account =>$balance ...exists.\n";
					}
			
			//TODO update 
			}
	}


function getBalance($account,$contract){
	$cmd='./bin/sophia/erts/bin/escript ./bin/sophia/aesophia_cli --create_calldata ./contracts/deploy/aex9.aes  --call "balance('.$account.')"';

	exec($cmd,$ret);	
	//print_r($ret);sleep(1);
	$callstr=$ret[1];
		
	$url="http://127.0.0.1:3113/v2/debug/transactions/dry-run";
	$jsonStr="{  \"accounts\": [    {      \"pub_key\": \"ak_2VuUB4fCN1eBoWnbTgdv3gBBgEYuc8NcdmyNWzuinyLZphfL1R\",      \"amount\": 0    }  ],  \"txs\": [    {           \"call_req\": {        \"calldata\": \"$callstr\",        \"contract\": \"$contract\",        \"amount\": 0,        \"gas\": 10000,        \"caller\": \"ak_2VuUB4fCN1eBoWnbTgdv3gBBgEYuc8NcdmyNWzuinyLZphfL1R\",        \"nonce\": 1,        \"abi_version\": 3              }    }  ]}";
	
	$post=http_post_json($url, $jsonStr);
	
	//print_r($post);sleep(1);
	$tmpstr=json_decode($post[1]);
	
	$call_data=$tmpstr->results[0]->call_obj->return_value;
	//echo $call_data."\n";
	$cmd="./bin/sophia/erts/bin/escript ./bin/sophia/aesophia_cli ./contracts/deploy/aex9.aes -b fate --call_result $call_data --call_result_fun meta_info";
	exec($cmd,$ret);	
	
	$amount=0;
	for($i=0;$i<count($ret);$i++){
		if(strpos($ret[$i],"variant,")>0 ){
			$tmpstr=explode(",{",$ret[$i]);
			$amount=str_replace("}}","",$tmpstr[1]);		
			}
		}	

	return $amount;
	}


function http_post_json($url, $jsonStr)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json; charset=utf-8',
				'Content-Length: ' . strlen($jsonStr)
			)
		);
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
	 
		return array($httpCode, $response);
	}


function decode_token_transfer($call_data,$decimal){//获取正确的返回调用
	$erlpath="./bin/sophia/erts/bin/escript";
	$clipath="./bin/sophia/aesophia_cli";
	$tokenaddress="./contracts/aex9.aes";
	
	$cmd="$erlpath $clipath $tokenaddress -b fate --call_result $call_data --call_result_fun meta_info";
	
	//echo "$cmd\n";
	exec($cmd,$ret);
	$addresstmp="";
	$amounttmp=0;
	for($i=0;$i<count($ret);$i++){
		if(strpos($ret[$i],"{address")>0 && strpos($ret[$i-1],"tuple,")>0){
			$addresstmp=$ret[$i+1].$ret[$i+2].$ret[$i+3];
			$amounttmp=$ret[$i+4];
			}
		}
	$addresstmp=str_replace("<<","",$addresstmp);
	$addresstmp=str_replace("\n","",$addresstmp);	
	$addresstmp=str_replace(">>},","",$addresstmp);	
	$amounttmp=str_replace("}}}}","",trim($amounttmp));	
	
	$data['address']=getAKfromHex(bin2hex(toAddress($addresstmp)));	
	$data['amount']=$amounttmp;
	
	return $data;
}
	
	
function getAKfromHex($hex){	
	$bs = pack("H*", $hex);
	$checksum = hash("sha256", hash("sha256", $bs, true));   
	$myhash=substr($checksum,0,8);
	$fullstr=$hex.$myhash;
	//echo "$fullstr\n";
	
	return "ak_". base58_encode(hex2bin($fullstr));
	}


function base58_encode($string)
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);
        if (is_string($string) === false) {
            return false;
        }
        if (strlen($string) === 0) {
            return '';
        }
        $bytes = array_values(unpack('C*', $string));
        $decimal = $bytes[0];
        for ($i = 1, $l = count($bytes); $i < $l; $i++) {
            $decimal = bcmul($decimal, 256);
            $decimal = bcadd($decimal, $bytes[$i]);
        }
        $output = '';
        while ($decimal >= $base) {
            $div = bcdiv($decimal, $base, 0);
            $mod = bcmod($decimal, $base);
            $output .= $alphabet[$mod];
            $decimal = $div;
        }
        if ($decimal > 0) {
            $output .= $alphabet[$decimal];
        }
        $output = strrev($output);
        foreach ($bytes as $byte) {
            if ($byte === 0) {
                $output = $alphabet[0] . $output;
                continue;
            }
            break;
        }
        return (string) $output;
    }
	
function toAddress($str){
	$mystr="";
	$tmpstr=explode(",",$str);
	for($i=0;$i<count($tmpstr);$i++){
		$mystr.=chr($tmpstr[$i]);
		}
	return $mystr;
	}



function getwebsrc($url) {
	global $pid, $pageerror;
	$curl = curl_init ();
	$agent = "User-Agent: AEKnow.org";
	
	curl_setopt ( $curl, CURLOPT_URL, $url );

	curl_setopt ( $curl, CURLOPT_USERAGENT, $agent );
	curl_setopt ( $curl, CURLOPT_ENCODING, 'gzip,deflate' );
	curl_setopt ( $curl, CURLOPT_FOLLOWLOCATION, 1 ); //×¥È¡301Ìø×ªºóÍøÖ·
	curl_setopt ( $curl, CURLOPT_AUTOREFERER, true );
	curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $curl, CURLOPT_TIMEOUT, 60 );
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

	
	$html = curl_exec ( $curl ); // execute the curl command
	$response_code = curl_getinfo ( $curl, CURLINFO_HTTP_CODE );
	if ($response_code != '200') { //Èç¹ûÎ´ÄÜ»ñÈ¡¸ÃÒ³Ãæ£¨·Ç200·µ»Ø£©£¬ÔòÖØÐÂ³¢ÊÔ»ñÈ¡
		echo 'Page error: ' . $response_code . $html;
		$pageerror = 1;
	
		//$pid=$pid+1;
	} else {
		//echo "\n" . $url . "  ==>GOT";
	
		//echo $response_code.'-';
	}
	curl_close ( $curl ); // close the connection
	

	return $html; // and finally, return $html
}


function GetTopHeight()	{
	$url=DATA_SRC_SITE."v2/blocks/top";
	$websrc=getwebsrc($url);
	$info=json_decode($websrc);
	if(strpos($websrc,"key_block")==TRUE){		
		return $info->key_block->height;
	}
		
	if(strpos($websrc,"micro_block")==TRUE){
		return $info->micro_block->height;
		}
	
	return 0;
	}


