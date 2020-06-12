<?php
//tx spider, crawl the latest transactions and save to db.
include "config.php";

UpdatAEX9Info();


function UpdatAEX9Info(){
	$conn_string = "host=aeknow.db port=5432 dbname=postgres password=".DB_PASS." user=".DB_USER;
	$db = pg_connect($conn_string);
	$sql = "SELECT txhash,contract_id FROM tx WHERE txtype='ContractCallTx' AND contract_id='ct_M9yohHgcLjhpp1Z8SaA1UTmRMQzR4FWjJHajGga8KBoZTEPwC' order by block_height";
	$result = pg_query($db, $sql);
	while ($row= pg_fetch_row($result)) {
		$txhash=$row[0];
		$contract_id=$row[1];
		$url=DATA_SRC_SITE."v2/transactions/$txhash";      
        $websrc=getwebsrc($url);
		$info=json_decode($websrc);
		
		$caller_id=$info->tx->caller_id;
		$call_data=$info->tx->call_data;
		$block_height=$info->block_height;	
		//print_r($info);
		//echo $url;sleep(100)	;
		CheckContracts($caller_id,$contract_id,$call_data,$block_height,$txhash);
		
		}
	}


function CheckContracts($caller_id,$contract_id,$call_data,$block_height,$txhash){
	$conn_string = "host=aeknow.db port=5432 dbname=postgres password=".DB_PASS." user=".DB_USER;
	$db = pg_connect($conn_string);
	$sql = "SELECT ctype,alias,decimal FROM contracts_token WHERE address='$contract_id'";
	//echo "$sql\n";sleep(1);
	$result_query = pg_query($db, $sql);
	echo ".";
	while ($row= pg_fetch_row($result_query)) {
		$ctype=$row[0];
		$alias=$row[1];
		$decimal=$row[2];
		}
		
	if($ctype=="AEX9"){
		$data=decode_token_transfer($call_data,$decimal);
		//print_r($data);sleep(1);
			if($data['amount']>0){		
				$account=$data['address'];
				$amount=$data['amount'];				
				$sql_update="UPDATE tx SET recipient_id='$account',amount=$amount WHERE txhash='$txhash'";	
				if(pg_query($db, $sql_update)){
					echo "U";
					}			
			}
		}
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


