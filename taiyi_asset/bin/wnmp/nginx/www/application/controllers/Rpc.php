<?php
class RpcController extends Ctrl_Base{
	static $_command = array(
		'getbalance' => array('string','int'),	
		'getrawtransaction' => array('string','int','int'),	
		'listtransactions' => array('string','int'),	
		'getreceivedbyaccount' => array('string','int'),	
		'getreceivedbyaddress' => array('string','int'),	
		'listaccounts' => array('int'),	
		'listreceivedbyaccount' => array('int','bool'),	
		'listreceivedbyaddress' => array('int','bool'),	
		'listunspent' => array('int','int'),	
		'move' => array('string','string','float','int','string'),	
		'sendfrom' => array('string','string','float','int','string','string'),	
		'sendmany' => array('string','int','string'),	
		'sendtoaddress' => array('string','float','string','string'),	
		'setgenerate' => array('bool','int'),	
		'settxfee' => array('float'),	
		'setgenerate' => array('bool','int'),	
		'importprivkey' => array('string','string','bool'),	
		'walletpassphrase' => array('string','int'),	
	);
    public function indexAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);		
		$sWalletinfo = $this->getwallet($pWallet);	

		#$tWalletinfo = Tool_Fnc::rpc_client($sWalletinfo)->getinfo();
		#echo '<pre>';
		#print_r($tWalletinfo);

		/*$tBlock = Tool_Fnc::rpc_client($sWalletinfo)->getblockcount();

		$tPeer = Tool_Fnc::rpc_client($sWalletinfo)->getpeerinfo();
		$tPeerheight = array();
		foreach($tPeer as $tRow){
			$tPeerheight[] = $tRow['height'];	
		}
		sort($tPeerheight);
		$tPeerheight = $tPeerheight[count($tPeerheight)-1];
		*/

		#$this->assign('tBlock' , $tBlock);
		#$this->assign('tPeerheight' , $tPeerheight);
		#$this->assign('tPeercount' , count($tPeer));
		#$this->assign('tWalletinfo' , $tWalletinfo);
		$this->assign('tWallet' , $pWallet);
		$this->display('rpc/index');
    }

	public function getAction(){
		$pCommand = empty($_GET['command'])?'':trim($_GET['command']);	
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);		
		if(empty($pCommand) || empty($pWallet)){exit;}
		$tCm = explode(' ', $pCommand);
		$tFun = strtolower($tCm[0]);
		unset($tCm[0]);

		$tWalletinfo = $this->getwallet($pWallet);	
		$tRpc = Tool_Fnc::rpc_client($tWalletinfo);
		if(count($tCm) > 0){
			$tNewCm = array();
			foreach($tCm as $tKey => $tV){
				if(empty($tV)) continue;
				$tNewCm[] = $tV;
			}
			foreach($tNewCm as $tKey => $tVal){
				if(!isset(self::$_command[$tFun])) break;	
				if(self::$_command[$tFun][$tKey] == 'int'){
					$tNewCm[$tKey] = (int)$tVal;	
				}elseif(self::$_command[$tFun][$tKey] == 'bool'){
					$tNewCm[$tKey] = (bool)$tVal;	
				}elseif(self::$_command[$tFun][$tKey] == 'float'){
					$tNewCm[$tKey] = (float)$tVal;	
				}
			}
			#$tNewCm = array('*',$a);
			$tRes = call_user_func_array(array($tRpc,$tFun),$tNewCm);
		}else{
			$tRes = call_user_func(array($tRpc,$tFun));
		}
		if($tFun == 'help'){
			$tRes = str_replace("\n",'<br />',$tRes);	
		}
		echo '<div class="output Font12 clearfix"><div class="left time">'.date('H:i:s').'</div><div class="left output_con"><h3 class="Font14">'.$tFun.'</h3><p class="font14">';
		echo $this->indent(json_encode($tRes));
		echo '</p></div></div>';
		exit;
	}
	private function indent ($json) { 
		$result = ''; 
		$pos = 0; 
		$strLen = strlen($json); 
		$indentStr = '&nbsp;&nbsp;&nbsp;&nbsp;'; 
		$newLine = "<br />"; 
		$prevChar = ''; 
		$outOfQuotes = true; 
 
		for ($i=0; $i<=$strLen; $i++) { 
			// Grab the next character in the string. 
			$char = substr($json, $i, 1); 
			// Are we inside a quoted string? 
			if ($char == '"' && $prevChar != '\\') { 
				$outOfQuotes = !$outOfQuotes; 
				// If this character is the end of an element, 
				// output a new line and indent the next line. 
			} else if(($char == '}' || $char == ']') && $outOfQuotes) { 
				$result .= $newLine; 
				$pos --; 
				for ($j=0; $j<$pos; $j++) { 
					$result .= $indentStr; 
				} 
			} 
			// Add the character to the result string. 
			$result .= $char; 
			// If the last character was the beginning of an element, 
			// output a new line and indent the next line. 
			if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) { 
				$result .= $newLine;
				if ($char == '{' || $char == '[') { 
					$pos ++; 
				} 
				for ($j = 0; $j < $pos; $j++) { 
					$result .= $indentStr; 
				} 
			} 
			$prevChar = $char; 
		} 

		return $result; 
	} 	

}
