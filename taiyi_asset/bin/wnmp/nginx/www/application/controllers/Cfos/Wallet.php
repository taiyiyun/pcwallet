<?php
class Cfos_WalletController extends Ctrl_Base{
    public function indexAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletinfo = $this->getwallet($pWallet);

		$tGetinfo = $this->getwalletcoin($tWalletinfo);
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->assign('tAct' , '');
		$this->display('cfos/wallet/index');
    }

	/*
	通过 listassets  测试是否此币可用
	0， 可用
	1， 不可用
	2,  出错
	*/
	public function isavailableAction(){
		if(empty($_GET['wallet'])){
			Tool_Fnc::ajaxMsg('empty' , 2 , array());
		}
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletinfo = $this->getwallet($pWallet);
		$tRows = Tool_Fnc::rpc_client($tWalletinfo)->listassets();
		if(isset($tRows['error'])){
			Tool_Fnc::ajaxMsg($tRows , 2 , array());
		}
		foreach($tRows as $tRow){
			if(strtoupper(trim($tRow["Symbol"])) == strtoupper($pWallet)){
				Tool_Fnc::ajaxMsg('' , 0 , array());
			}			
		}
		Tool_Fnc::ajaxMsg($tRows , 1 , array());
	}
    public function listaddressgroupingsAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletinfo = $this->getwallet($pWallet);
		$rpcWalletinfo = Tool_Fnc::rpc_client($tWalletinfo)->getinfo();

		$tList = Tool_Fnc::rpc_client($tWalletinfo)->listaddressgroupings();
		exit;
    }

	/**
	 * 接受地址
	 */
	public function listreceivedbyaddressAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$pBackdata = empty($_GET['backdata'])?'':trim($_GET['backdata']);

		if(!empty($pBackdata)){
			$tWalletinfo = $this->getwallet($pWallet);
			$tList = Tool_Fnc::rpc_client($tWalletinfo)->getaddressesbyaccount('default');
			foreach($tList as $tKey => $tRow){
				if($tRow['symbol'] != $pWallet){unset($tList[$tKey]);}
			}
			$tListdata = Tool_Fnc::rpc_client($tWalletinfo)->listaddressgroupings();
			$tBalance = array();
			foreach($tListdata as $tRows){
				foreach($tRows as $tRow){
					if($tRow["symbol"] != $pWallet){continue;}
					$tBalance[$tRow["address"]] = $tRow["amount"];
				}
			}

			if(count($tList)){

				$this->checkaddress($pWallet,$tList,$tWalletinfo);

				$tData = array();
				foreach($tList as $tkey => $tRow){
					if($tRow['symbol'] != $pWallet){unset($tList[$tkey]);}
					$tIsmine = $this->sqlite('appdata')->getRow('select ismine,created from address where mark = \'' .$pWallet. '\' and address=\''.$tRow['address'].'\'');
					if(empty($tIsmine['ismine']) || $tIsmine['ismine'] != 1){
						continue;
					}
					$tRow['created'] = $tIsmine['created'];
					$tRow['balance'] = empty($tBalance[$tRow['address']])?0:$tBalance[$tRow['address']];
					$tData[$tkey] = $tRow;
				}
				foreach($tData as $tKey=> $tRow){
					$tData[$tKey]['account'] =  empty($tRow['account'])?$this->lang->wallet->default_address:$tRow['account'];
					$tLabel =  $this->getLabel($tRow['address'],$pWallet);
					if(!empty($tLabel)){
						$tData[$tKey]['account'] = $tLabel;
					}
					$tData[$tKey]['balance'] = Tool_Fnc::etonumber($tData[$tKey]['balance']);
				}

				$tData = Tool_Fnc::arraySort($tData,'created');

				Tool_Fnc::ajaxMsg('' , 1 , $tData);
			}else{
				Tool_Fnc::ajaxMsg($this->lang->wallet->message3. empty($tList['error'])?'':$tList['error'] , 0);
			}

		}

		$this->assign('tWallet' , $pWallet);
		$this->assign('tAct' , 'listreceivedbyaddress');
		$this->display('cfos/wallet/listreceivedbyaddress');
	}
	/**
	 * 添加地址
	 */
	public function addaddresstolistAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$pAdd = empty($_GET['add'])?0:$_GET['add'];

		if(!empty($pAdd)){
			$pId = time();
			$pMark = $pWallet;
			$pAddress = empty($_POST['address'])?'':trim($_POST['address']);
			$pAccount = empty($_POST['account'])?'':trim($_POST['account']);
			$pAmount = empty($_POST['amount'])?0:floatval(trim($_POST['amount']));
			$pPayaddress = empty($_POST['payaddress'])?'':trim($_POST['payaddress']);

			if(empty($pAddress) ||  empty($pPayaddress)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->index_error , 0);
			}
			if(!empty($pAccount) && !Tool_Validate::name($pAccount)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->message7 , 0);
			}
			if(empty($pAmount)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->amount_not_empty, 0);
			}
			if($pAmount < 0.00000001){
				Tool_Fnc::ajaxMsg($this->lang->wallet->message17, 0);
			}
			Tool_Fnc::sqliteLock();
			$value = $pId.',\''.$pAccount.'\',\''.$pMark.'\',\''.$pAddress.'\',\''.$pPayaddress.'\','.$pAmount;
			$cmd = 'insert into sendlist values('.$value.')';
			$this->sqlite()->query($cmd);
			Tool_Fnc::sqliteCloseLock();
			Tool_Fnc::ajaxMsg('', 1 , array());
		}
	}
	
	public function listtransactionsAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$pBackdata = empty($_GET['backdata'])?'':trim($_GET['backdata']);

		if(!empty($pBackdata)){

			$tWalletinfo = $this->getwallet($pWallet);
			$tGetinfo = $this->getwalletcoin($tWalletinfo);
			$tList = Tool_Fnc::rpc_client($tWalletinfo)->listsinceblock();

			$tList = empty($tList['transactions'])?array():$tList['transactions'];

			$tDarr = array('receive' => $this->lang->main->receive , 'send' => $this->lang->main->send);
			if(count($tList)){
				$tMList = new ListtransactionsModel;
				$tList = $tMList->listtransactions_exclude($tList);
				foreach($tList as $tKey => $tRow){
					if($tRow['symbol'] != $pWallet){unset($tList[$tKey]);continue;}
					$tList[$tKey]['date'] = date('Y-m-d H:i:s' , $tRow['time']);
					$tList[$tKey]['address'] = empty($tRow['address'])?'(n/a)':$tRow['address'];
					$tList[$tKey]['category'] = empty($tDarr[$tRow['category']])?$tRow['category']:$tDarr[$tRow['category']];
					$tList[$tKey]['explorer'] = $tWalletinfo['explorer'];
				}
				$tList = Tool_Fnc::arraySort($tList,'time');
				$tNewList = array();
				foreach($tList as $tRow){
					$tNewList[] = $tRow;
				}
				$tList = $tNewList;

				//显示标签
				foreach($tList as $tKey => $tRow){
					$tData = $this->sqlite('appdata')->getRow('select label from address where mark = \''.$pWallet.'\' and address = \''.$tRow['address'].'\'');
					$tLabel = empty($tData['label'])?$this->lang->wallet->no_label:$tData['label'];
					$tList[$tKey]['label'] = $tLabel;
				}
				Tool_Fnc::ajaxMsg('' , 1 , $tList);
			}else{
				Tool_Fnc::ajaxMsg($this->lang->wallet->message4 . empty($tList['error'])?'':$tList['error'] , 0);
			}
			$this->assign('tAct' , 'more');
			$this->assign('tWallet' , $pWallet);
		}else{
			$this->assign('tAct' , 'more');
			$this->assign('tWallet' , $pWallet);
			$this->display('cfos/wallet/listtransactions');
		}
	}

	private function getwalletcoin($pWallet){
		$rpcWalletinfo = Tool_Fnc::rpc_client($pWallet)->getinfo();
		return $rpcWalletinfo;
	}
	//显示地址
	public function sendlistAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$pClear = empty($_GET['clear'])?0:$_GET['clear'];
		if(!empty($pClear)){
			$this->sqlite()->query('delete from sendlist');
		}
		$pBackdata = empty($_GET['backdata'])?'':trim($_GET['backdata']);
		if(!empty($pBackdata)){
			$cmd = 'select * from sendlist';
			$tList = $this->sqlite()->getAll($cmd);
			if(count($tList)){
				Tool_Fnc::ajaxMsg('' , 1 , $tList);
			}else{
				Tool_Fnc::ajaxMsg($this->lang->wallet->message4 . empty($tList['error'])?'没有数据':$tList['error'] , 0);
			}
		}else{
			$this->assign('tAct' , 'more');
			$this->assign('tWallet' , $pWallet);
			$this->display('cfos/wallet/sendlist');
		}
	}
	/**
	 * 发送
	 */
	 public function sendtoaddressAction(){
		$pSent = empty($_GET['sent'])?0:$_GET['sent'];
		if(!empty($pSent)){
			$id = empty($_GET['label'])?'':trim($_GET['label']);
			if(empty($id)){
				Tool_Fnc::ajaxMsg('invalid id' , 0);
			}
			$tId = $this->sqlite()->getRow('select * from sendlog where id=='.$id);
			if(!empty($tId)){
				Tool_Fnc::ajaxMsg('wait...' , 0);
				exit();
			}
			Tool_Fnc::sqliteLock();
			$this->sqlite()->query('insert into sendlog values('.$id.')');
			Tool_Fnc::sqliteCloseLock();
		}
		
		$cmd = 'select * from sendlist';
		$tList = $this->sqlite()->getAll($cmd);
		if(count($tList)<1){
			$this->sendtooneaddressAction(array());
		}
		if(count($tList)==1){
			$this->sendtooneaddressAction($tList[0]);
		}
		if(count($tList)>1){
			$this->sendmanyAction($tList);
		}
	 }
	/**
	 * 发送一个
	 */
	private function sendtooneaddressAction($tData){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$pSent = empty($_GET['sent'])?0:$_GET['sent'];
		$pAccount = empty($_GET['account'])?'':trim($_GET['account']);
		$pAddress = empty($_GET['address'])?'':trim($_GET['address']);
		Tool_Fnc::sqliteLock();
		$tWalletinfo = $this->getwallet($pWallet);
		if(!empty($pSent)){
			$pAddress = empty($tData['address'])?'':trim($tData['address']);
			$pAmount = empty($tData['amount'])?0:floatval(trim($tData['amount']));
			$pAccount = empty($tData['account'])?'':trim($tData['account']);
			$pPassword = empty($tData['password'])?'':trim($tData['password']);
			$pPayaddress = empty($tData['payaddress'])?'':trim($tData['payaddress']);
			
			if(empty($pAddress) ||  empty($pPayaddress)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->index_error , 0);
			}
			if(empty($pAmount)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->amount_not_empty, 0);
			}
			if($pAmount < 0.00000001){
				Tool_Fnc::ajaxMsg($this->lang->wallet->message17, 0);
			}

			if(!empty($pAccount) && !Tool_Validate::name($pAccount)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->message7 , 0);
			}
			if(isset($tData['password']) && empty($pPassword)){
				Tool_Fnc::ajaxMsg($this->lang->system->encrypted_wallet_password,0);
			}
			
			if($pWallet == 'ybcoin' && substr($pAddress,0,1) == 'Y'){
				//Tool_Fnc::ajaxMsg('元宝币的新老钱包机制不同，禁止新老钱包互转。一旦转出，币将不会到账，且无法找回 !' , 0);
				Tool_Fnc::ajaxMsg($this->lang->wallet->message6 , 0);
			}

			$tGetinfo = Tool_Fnc::rpc_client($tWalletinfo)->getinfo();
			if(empty($tGetinfo['balance']) || $tGetinfo['balance'] < $pAmount){
				Tool_Fnc::ajaxMsg($this->lang->wallet->insufficient_balance , 0);
			}

			if(!empty($pPassword)){
				Tool_Fnc::rpc_client($tWalletinfo)->walletlock();

				$tPassphrase = Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase($pPassword,60);
				if(!is_null($tPassphrase['error']) && $tPassphrase['error']['code'] != '-17'){
					$tError = '';
					if($tPassphrase['error']['code'] == '-14' && $this->l =='cn'){
						$tError = $this->lang->wallet->message11;
					}else{
						$tError = $tPassphrase['error']['message'];
					}
					if(empty($tWalletinfo['unlock'])){
						Tool_Fnc::ajaxMsg($tError  , 0);
					}else{
						$this->sqlite()->query('update mywallet set unlock=0 where mark = \''.$pWallet.'\' and pos=1');
						Tool_Fnc::ajaxMsg($tError . '<br />' . $this->lang->wallet->message10  , 0);
					}
				}

			}
			
			$tTend = $pPayaddress;
			if($pWallet != 'ABC'){
				$tTend = $this->getTendaddress($tWalletinfo);
				$tTend = $tTend['address'];

				$tRes = Tool_Fnc::rpc_client($tWalletinfo)->sendassettoaddress($pPayaddress,$pAddress,$pAmount,$tTend,$tTend,$pPayaddress);
			}else{

				$tRes = Tool_Fnc::rpc_client($tWalletinfo)->sendassettoaddress($pPayaddress,$pAddress,$pAmount,$tTend,$tTend);
			}

			if(!isset($tRes[0]['txid'])){
				$tError = '';
				if($tRes['error']['code'] == '-26'){
					$tError = $this->lang->wallet->error_26;
				}elseif($tRes['error']['code'] == '-6' || $tRes['error']['code'] == '-1'){
					$tError = $this->lang->wallet->error_6;	#现金手续费不足
				}elseif($tRes['error']['code'] == '-4' && $this->l == 'cn'){
					$tError = $this->lang->wallet->insufficient_funds;
				}elseif($tRes['error']['code'] == '-28'){
					$tError = $this->lang->wallet->error_28;
				}elseif($tRes['error']['code'] == '-38'){
					$tError = $this->lang->wallet->error_38;
				}else{
					$tError = $tRes['error']['message'] . ' ' . $tRes['error']['code'];
				}
				Tool_Fnc::ajaxMsg( $tError , 0);
			}



			Tool_Fnc::rpc_client($tWalletinfo)->walletlock();
			//判断是否开启pos
			$tIspos = $this->sqlite()->getRow('select count(*) c from mywallet where mark = \''.$pWallet.'\' and pos = 1 and unlock = 1');
			if(!empty($tIspos['c'])){
				Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase($pPassword , 30758400 , true);
			}

			if( (is_array($txid) && !is_null($txid['error']))){
				if($txid['error']['code'] == '-4' && $this->l == 'cn'){
					$tError = $this->lang->wallet->insufficient_funds;
				}elseif($txid['error']['code'] == '-13' && $this->l == 'cn'){
					$tError = $this->lang->wallet->message12;
				}else{
					$tError = $txid['error']['message'];
				}
				Tool_Fnc::ajaxMsg($this->lang->wallet->sending_coin_failed.':' . $tError , 0);
			}

			if($tIsminedata = $this->ismineaddress($tWalletinfo,$pAddress)){
				$tIsmine = 1;$tLabel = 'default';
				if($tIsminedata['ismine'] == false){$tIsmine = 2;$tLabel = 'book';}
				Tool_Fnc::rpc_client($tWalletinfo)->setaccount($pAddress,$tLabel);
				$this->address($pAccount,$pAddress,$pWallet,$tIsmine);
			}

			Cache_File::set('listtransactions:'.$pWallet,'');
			Cache_File::set('walletinfo:'.$pWallet,'');

			Tool_Fnc::sqliteCloseLock();
			Tool_Fnc::ajaxMsg($this->lang->wallet->sending_coin_successful, 1 , array('txid' => $txid));
		}else{

			$tPassphrase =Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase('zyr123',60);
			$tPasswallet = 0;
			if(!is_null($tPassphrase['error']) && $tPassphrase['error']['code'] == '-15'){
				$tPasswallet = 1;//钱包没有密码
			}

		}
		//选择支付地址
		$tPayaddressdata = Tool_Fnc::rpc_client($tWalletinfo)->listaddressgroupings();
		$tPayaddress = array();
		foreach($tPayaddressdata as $tRows){
			foreach($tRows as $tRow){
			if($tRow["symbol"] != $pWallet || empty($tRow["amount"])){continue;}
			$tPayaddress[] = array('address'=>$tRow["address"],'balance'=>Tool_Fnc::etonumber($tRow["amount"]),'label' => $this->getLabel($tRow["address"],$pWallet));
			}
		}
		$tPayaddress = Tool_Fnc::arraySort($tPayaddress,'balance');

		$tGetinfo = $this->getwalletcoin($tWalletinfo);

		#Tool_Fnc::sqliteCloseLock();
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tPayaddress' , $tPayaddress);
		$this->assign('pAccount' , $pAccount);
		$this->assign('pAddress' , $pAddress);
		$this->assign('tPasswallet' , $tPasswallet);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->assign('tAct' , 'sendtoaddress');
		$this->display('cfos/wallet/sendtoaddress');
	}
	/**
	 * 发送多个
	 */
	private function sendmanyAction($tDatas){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$pSent = empty($_GET['sent'])?0:$_GET['sent'];
		$pAccount = empty($_GET['account'])?'':trim($_GET['account']);
		$pAddress = empty($_GET['address'])?'':trim($_GET['address']);

		Tool_Fnc::sqliteLock();
		$tWalletinfo = $this->getwallet($pWallet);
		if(!empty($pSent)){
			$tData = $tDatas[0];
			$cmd = 'select address amount from sendlist';
			$pList = $this->sqlite()->getAll($cmd);
			$pAddr_Amount_a = array();
			foreach($tDatas as $tkey=>$tDa){
				$pAddr_Amount_a[$tDa['address']] = $tDa['amount'];
			}
			$pAddr_Amount =  ($pAddr_Amount_a);
			
			$pAddress = empty($tData['address'])?'':trim($tData['address']);
			$pAmount = empty($tData['amount'])?0:floatval(trim($tData['amount']));
			$pAccount = empty($tData['account'])?'':trim($tData['account']);
			$pPassword = empty($tData['password'])?'':trim($tData['password']);
			$pPayaddress = empty($tData['payaddress'])?'':trim($tData['payaddress']);

			if(!empty($pPassword)){
				Tool_Fnc::rpc_client($tWalletinfo)->walletlock();

				$tPassphrase = Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase($pPassword,60);
				if(!is_null($tPassphrase['error']) && $tPassphrase['error']['code'] != '-17'){
					$tError = '';
					if($tPassphrase['error']['code'] == '-14' && $this->l =='cn'){
						$tError = $this->lang->wallet->message11;
					}else{
						$tError = $tPassphrase['error']['message'];
					}
					if(empty($tWalletinfo['unlock'])){
						Tool_Fnc::ajaxMsg($tError  , 0);
					}else{
						$this->sqlite()->query('update mywallet set unlock=0 where mark = \''.$pWallet.'\' and pos=1');
						Tool_Fnc::ajaxMsg($tError . '<br />' . $this->lang->wallet->message10  , 0);
					}
				}

			}
			
			$tRes = $this->dosendmany($tWalletinfo,$pWallet,$pPayaddress,$pAddr_Amount);
			if(!isset($tRes[0]['txid'])){
				$tError = '';
				if($tRes['error']['code'] == '-26'){
					$tError = $this->lang->wallet->error_26;
				}elseif($tRes['error']['code'] == '-6' || $tRes['error']['code'] == '-1'){
					$tError = $this->lang->wallet->error_6;	#现金手续费不足
				}elseif($tRes['error']['code'] == '-4' && $this->l == 'cn'){
					$tError = $this->lang->wallet->insufficient_funds;
				}elseif($tRes['error']['code'] == '-28'){
					$tError = $this->lang->wallet->error_28;
				}elseif($tRes['error']['code'] == '-38'){
					$tError = $this->lang->wallet->error_38;
				}else{
					$tError = $tRes['error']['message'] . ' ' . $tRes['error']['code'];
				}
				Tool_Fnc::ajaxMsg( $tError , 0, $tRes['error']);
			}


			Tool_Fnc::rpc_client($tWalletinfo)->walletlock();
			//判断是否开启pos
			$tIspos = $this->sqlite()->getRow('select count(*) c from mywallet where mark = \''.$pWallet.'\' and pos = 1 and unlock = 1');
			if(!empty($tIspos['c'])){
				Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase($pPassword , 30758400 , true);
			}

			if( (is_array($txid) && !is_null($txid['error']))){
				if($txid['error']['code'] == '-4' && $this->l == 'cn'){
					$tError = $this->lang->wallet->insufficient_funds;
				}elseif($txid['error']['code'] == '-13' && $this->l == 'cn'){
					$tError = $this->lang->wallet->message12;
				}else{
					$tError = $txid['error']['message'];
				}
				Tool_Fnc::ajaxMsg($this->lang->wallet->sending_coin_failed.':' . $tError , 0);
			}

			Cache_File::set('listtransactions:'.$pWallet,'');
			Cache_File::set('walletinfo:'.$pWallet,'');

			Tool_Fnc::sqliteCloseLock();
			Tool_Fnc::ajaxMsg($this->lang->wallet->sending_coin_successful, 1 , array('txid' => $txid));
		}else{

			$tPassphrase =Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase('zyr123',60);
			$tPasswallet = 0;
			if(!is_null($tPassphrase['error']) && $tPassphrase['error']['code'] == '-15'){
				$tPasswallet = 1;//钱包没有密码
			}

		}

		//选择支付地址
		$tPayaddressdata = Tool_Fnc::rpc_client($tWalletinfo)->listaddressgroupings();
		$tPayaddress = array();
		foreach($tPayaddressdata as $tRows){
			foreach($tRows as $tRow){
			if($tRow["symbol"] != $pWallet || empty($tRow["amount"])){continue;}
			$tPayaddress[] = array('address'=>$tRow["address"],'balance'=>Tool_Fnc::etonumber($tRow["amount"]),'label' => $this->getLabel($tRow["address"],$pWallet));
			}
		}
		$tPayaddress = Tool_Fnc::arraySort($tPayaddress,'balance');

		$tGetinfo = $this->getwalletcoin($tWalletinfo);

		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tPayaddress' , $tPayaddress);
		$this->assign('pAccount' , $pAccount);
		$this->assign('pAddress' , $pAddress);
		$this->assign('tPasswallet' , $tPasswallet);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->assign('tAct' , 'sendtoaddress');
		$this->display('cfos/wallet/sendtoaddress');
	}
	/**
	 * 得到现金支付地址
	 */
	private function getTendaddress($tWalletinfo){
		$tPayaddressdata = Tool_Fnc::rpc_client($tWalletinfo)->listaddressgroupings();
		$tPayaddress = array();
		foreach($tPayaddressdata as $tRows){
			foreach($tRows as $tRow){
			if($tRow["symbol"] != 'ABC'){continue;}
			$tPayaddress[] = array('address'=>$tRow["address"],'balance'=>$tRow["amount"]);
			}
		}
		$tPayaddress = Tool_Fnc::arraySort($tPayaddress,'balance');

		return empty($tPayaddress[0])?array():$tPayaddress[0];
	}
	private function sendassettoaddress($pWallet,$pAddress,$pMoney){

		$tWalletinfo = $this->getwallet($pWallet);
		$tCoinsinfo = Tool_Fnc::rpc_client($tWalletinfo)->listaddressgroupings();
		if(!count($tCoinsinfo) || !is_null($tCoinsinfo['error'])){Tool_Fnc::ajaxMsg( $this->lang->mywallet->acquiring_wallet_data_abnormal , '0' , $tCoinsinfo['error'] );}
		$tData = array();
		$tMoneytotal = 0;
		#组合返回值
		foreach($tCoinsinfo as $tKey => $tRow){
			foreach($tRow as $tV){
				if($tV["symbol"] != $pWallet){continue;}
				$tData[] = array('address' => $tV["address"],'balance' => $tV["amount"]);
				$tMoneytotal += $tV["amount"];
			}
		}
		$tDatasend = array();
		$tMoney_sy = $pMoney;
		$tRealsend = 0;
		#获取发送信息
		foreach($tData as $tRow){
			if(empty($tRow['balance'])){continue;}
			$tMoney_sy = $pMoney-$tRow['balance'];
			if($tMoney_sy <= 0){
				$tRealsend += $tRow['balance'];
				$tDatasend[] = array('from' => $tRow['address'],'to'=>$pAddress,'money'=>$pMoney,'cashaddress'=>$tRow['address']);
				break;
			}else{
				$tRealsend += $tMoney_sy;
				$tDatasend[] = array('from' => $tRow['address'],'to'=>$pAddress,'money'=>$tRow['balance'],'cashaddress'=>$tRow['address']);
			}
		}
		if($pMoney > $tRealsend){
			Tool_Fnc::ajaxMsg('可用币不足' , 0);
		}
		foreach($tDatasend as $tRow){
			$tTendaddress = $tRow['from'];
			if($pWallet != 'ABC'){
				$tTendaddress = $this->getTendinfo($tWalletinfo);
			}


			$tRes = Tool_Fnc::rpc_client($tWalletinfo)->sendassettoaddress($tRow['from'],$tRow['to'],$tRow['money'],$tTendaddress);
			if(!isset($tRes[0]['txid'])){
				Tool_Fnc::ajaxMsg($tRes , 0);
			}
		}
	}
	/**
	 * 获取现金币 信息
	 */
	private function getTendinfo($pWalletinfo){
		$tRes = Tool_Fnc::rpc_client($pWalletinfo)->listaddressgroupings();
		$tData = array();
		$tMoney = 0;
		$tAddress = 0;
		foreach($tRes as $tRow){
			foreach($tRow as $tV){
				if($tV["symbol"] != 'ABC'){continue;}
				if($tMoney < $tV["amount"]){$tAddress = $tV["address"];}
			}
		}
		return $tAddress;
	}

	/**
	 * 添加接受地址
	 */
	public function listreceivedbyaddressaddAction(){

		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletinfo = $this->getwallet($pWallet);

		$tPassphrase =Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase('zyr123',60);
		$tPasswallet = 0;
		if(!is_null($tPassphrase['error']) && $tPassphrase['error']['code'] == '-15'){
			$tPasswallet = 1;//钱包没有密码
		}

		if(count($_POST)){
			$pAccount = empty($_POST['account'])?'':trim($_POST['account']);
			if(empty($pWallet)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->wallet_type_cannot_be_empty , 0);
			}

			if(empty($pAccount)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->address_label_cannot_be_empty , 0);
			}
			if(!Tool_Validate::name($pAccount)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->message7 , 0);
			}
			if(isset($_POST['password']) && empty($_POST['password'])){
				Tool_Fnc::ajaxMsg($this->lang->system->password_for_encrypted_wallet,0);
			}

			$tIspassword = 0;
			if(isset($_POST['password']) && !empty($_POST['password'])){
				$tPassphrase = Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase($_POST['password'],60);
				if(!is_null($tPassphrase['error']) && $tPassphrase['error']['code'] != '-17'){
					$tError = '';
					if($tPassphrase['error']['code'] == '-14' && $this->l =='cn'){
						$tError = $this->lang->wallet->message11;
					}else{
						$tError = $tPassphrase['error']['message'];
					}
					Tool_Fnc::ajaxMsg($tError,0);
				}
				$tIspassword = 1;
			}

			$tRes = Tool_Fnc::rpc_client($tWalletinfo)->getnewaddress($pWallet);

			if(empty($tRes) || (!is_null($tRes['error']) && is_array($tRes))){
				Tool_Fnc::ajaxMsg($tRes['error']['message'] , 0);
			}
			$this->address($pAccount,$tRes,$pWallet,1);
			if(!empty($tIspassword)){Tool_Fnc::rpc_client($tWalletinfo)->walletlock();}

			Tool_Fnc::ajaxMsg($this->lang->wallet->added_successfully , 1);
		}

		$tGetinfo = $this->getwalletcoin($tWalletinfo);

		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tAct' , 'more');
		$this->assign('tWallet' , $pWallet);
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->assign('tPasswallet' , $tPasswallet);
		$this->display('cfos/wallet/listreceivedbyaddressadd');
	}

	/**
	 * 地址薄添加
	 */
	public function sendaddressaddAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletinfo = $this->getwallet($pWallet);

		#Tool_Fnc::sqliteLock();
		if(count($_POST)){
			$pAddress = empty($_POST['address'])?'':trim($_POST['address']);
			$pAccount = empty($_POST['account'])?'':trim($_POST['account']);
			if(empty($pWallet)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->wallet_type_cannot_be_empty , 0);
			}

			if(empty($pAddress)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->address_cannot_be_empty, 0);
			}
			if(!Tool_Validate::name($pAccount)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->message7 , 0);
			}

			$tWalletinfo = $this->getwallet($pWallet);
			$tLA = Tool_Fnc::rpc_client($tWalletinfo)->listaddresses();
			foreach($tLA as $tRow){
				if($tRow['address'] == $pAddress){
					Tool_Fnc::ajaxMsg($this->lang->wallet->the_address_has_been_added , 0);
				}
			}

			//$tRes = Tool_Fnc::rpc_client($tWalletinfo)->setaccount($pAddress , $pAccount);
			$tRes = Tool_Fnc::rpc_client($tWalletinfo)->setaccount($pAddress,'book');
			if((!is_null($tRes['error']) && is_array($tRes))){
				$tError = '';
				if($tRes['error']['code'] == '-5' && $this->l == 'cn'){
					$tError = $this->lang->wallet->invalid_address;
				}else{
					$tError = $tRes['error']['message'];
				}
				Tool_Fnc::ajaxMsg($tError , 0);
			}
			$this->address($pAccount,$pAddress,$pWallet,2);
			#Tool_Fnc::sqliteCloseLock();
			Tool_Fnc::ajaxMsg($this->lang->wallet->added_successfully , 1);
		}
		$tGetinfo = $this->getwalletcoin($tWalletinfo);
		#Tool_Fnc::sqliteCloseLock();
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tAct' , 'more');
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->display('cfos/wallet/sendaddressadd');
	}
	public function sendaddresseditAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletinfo = $this->getwallet($pWallet);
		$pAddress = empty($_GET['address'])?'':trim($_GET['address']);
		$pAccount = empty($_GET['account'])?'':trim($_GET['account']);
		$pMysubmit = empty($_GET['mysubmit'])?'':trim($_GET['mysubmit']);

		#Tool_Fnc::sqliteLock();
		if(!empty($pMysubmit)){

			if(empty($pWallet)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->wallet_type_cannot_be_empty , 0);
			}

			if(empty($pAccount)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->Label_cannot_be_empty , 0);
			}

			if(empty($pAddress)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->address_cannot_be_empty , 0);
			}
			if(!Tool_Validate::name($pAccount)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->message7 , 0);
			}

			$tWalletinfo = $this->getwallet($pWallet);

			//$tRes = Tool_Fnc::rpc_client($tWalletinfo)->editaccount($pAddress , $pAccount);
			$tValidaddress = Tool_Fnc::rpc_client($tWalletinfo)->validateaddress($pAddress);
			if(empty($tValidaddress['isvalid'])){
				Tool_Fnc::ajaxMsg('此地址无效' , 0);
			}
			$tIsmine = 1;if($tValidaddress['ismine']==false){$tIsmine = 2;}
			$this->address($pAccount,$pAddress,$pWallet,$tIsmine);

			if((!is_null($tRes['error']) && is_array($tRes))){
				Tool_Fnc::ajaxMsg($tRes['error']['message'] , 0);
			}
			#Tool_Fnc::sqliteCloseLock();
			Tool_Fnc::ajaxMsg($this->lang->wallet->changed_successfully , 1);
		}
		#Tool_Fnc::sqliteCloseLock();
		$this->assign('tAddress' , $pAddress);
		$this->assign('tAccount' , $pAccount);
		$tGetinfo = $this->getwalletcoin($tWalletinfo);
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tAct' , 'more');
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->display('cfos/wallet/sendaddressedit');
	}

	public function listreceivedbyaddresseditAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletinfo = $this->getwallet($pWallet);
		$pAddress = empty($_GET['address'])?'':trim($_GET['address']);
		$pAccount = empty($_GET['account'])?'':trim($_GET['account']);
		$pMysubmit = empty($_GET['mysubmit'])?'':trim($_GET['mysubmit']);
		if($pAccount == $this->lang->wallet->default_address){
			$pAccount = '';
		}


		#Tool_Fnc::sqliteLock();
		if(!empty($pMysubmit)){
			if(empty($pWallet)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->wallet_type_cannot_be_empty , 0);
			}

			if(empty($pAccount)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->Label_cannot_be_empty , 0);
			}
			if(!Tool_Validate::name($pAccount)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->message7 , 0);
			}

			if(empty($pAddress)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->address_cannot_be_empty , 0);
			}

			$tWalletinfo = $this->getwallet($pWallet);

			#$tRes = Tool_Fnc::rpc_client($tWalletinfo)->editaccount($pAddress , iconv('utf-8','gb2312',$pAccount));
			$tValidaddress = Tool_Fnc::rpc_client($tWalletinfo)->validateaddress($pAddress);
			if(empty($tValidaddress['isvalid'])){
				Tool_Fnc::ajaxMsg('此地址无效' , 0);
			}
			$tIsmine = 1;if($tValidaddress['ismine']==false){$tIsmine = 2;}

			$this->address($pAccount,$pAddress,$pWallet,$tIsmine);
			#Tool_Fnc::sqliteCloseLock();
			Tool_Fnc::ajaxMsg($this->lang->wallet->changed_successfully , 1);
		}

		$tGetinfo = $this->getwalletcoin($tWalletinfo);
		#Tool_Fnc::sqliteCloseLock();
		$this->assign('tAddress' , $pAddress);
		$this->assign('tAccount' , $pAccount);
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tAct' , 'more');
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->display('cfos/wallet/listreceivedbyaddressedit');
	}

	public function moreAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletinfo = $this->getwallet($pWallet);

		#Tool_Fnc::sqliteLock();
		$tGetinfo = $this->getwalletcoin($tWalletinfo);
		#Tool_Fnc::sqliteCloseLock();
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tAct' , 'more');
		$this->display('cfos/wallet/more');
	}


	/**
	 * 发送地址
	 */
	function sendaddressAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);

		/*$tWalletinfo = $this->getwallet($pWallet);
		$tList = Tool_Fnc::rpc_client($tWalletinfo)->getaddressesbyaccount('book');
		foreach($tList as $tKey => $tRow){
			if($tRow['symbol'] != $pWallet){unset($tList[$tKey]);continue;}
			$tGetlabel = $this->getLabel($tRow['address'],$pWallet);
			if(!empty($tGetlabel)){
				$tList[$tKey]['account'] = $tGetlabel;
			}
		}
		*/
		$pBackdata = empty($_GET['backdata'])?'':trim($_GET['backdata']);
		$tWalletinfo = $this->getwallet($pWallet);
		#Tool_Fnc::sqliteLock();
		if(!empty($pBackdata)){
			#$tList = Tool_Fnc::rpc_client($tWalletinfo)->listreceivedbyaddress(0,true);
			$tList = Tool_Fnc::rpc_client($tWalletinfo)->getaddressesbyaccount('book');
			if(count($tList) && !isset($tList['error'])){
				foreach($tList as $tKey=> $tRow){
					if($tRow['symbol'] != $pWallet){unset($tList[$tKey]);continue;}
				}
				$this->checkaddress($pWallet,$tList,$tWalletinfo);

				$tData = array();

				$tIsmine = $this->sqlite('appdata')->getAll('select ismine,address,label,created from address where mark = \'' .$pWallet. '\'');
				$tIsminearray = array();
				foreach($tIsmine as $tRow){$tIsminearray[$tRow['address']] = array('ismine' => $tRow['ismine'],'account' => $tRow['label'],'created' => $tRow['created']);}
				foreach($tList as $tKey => $tRow){
					if(empty($tIsminearray[$tRow['address']]['ismine']) || $tIsminearray[$tRow['address']]['ismine']!= 2){
						continue;
					}
					$tRow['created'] = $tIsminearray[$tRow['address']]['created'];
					$tData[$tKey] = $tRow;
					$tData[$tKey]['account'] =  empty($tRow['account'])?$this->lang->wallet->default_address:$tRow['account'];
					$tLabel =  $tIsminearray[$tRow['address']]['account'];
					if(!empty($tLabel)){
						$tData[$tKey]['account'] = $tLabel;
					}
				}
				$tData = Tool_Fnc::arraySort($tData,'created');
				#Tool_Fnc::sqliteCloseLock();
				Tool_Fnc::ajaxMsg('' , 1 , $tData);
			}
			Tool_Fnc::ajaxMsg('' , 0 );
		}
		$tGetinfo = $this->getwalletcoin($tWalletinfo);
		#Tool_Fnc::sqliteCloseLock();

		if(!isset($tList)){
			$tList = array();
		}
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->assign('tList' , $tList);
		$this->assign('tAct' , 'more');
		$this->display('cfos/wallet/sendaddress');
	}

	/**
	 * 操作 address 表
	 */
	private function address($pLabel , $pAddress , $pWallet , $pIsmine){

        $tRow =$this->sqlite('appdata')->getRow('select * from address where address = \'' .$pAddress. '\' and mark =\''.$pWallet.'\'');
		$tSql = '';
		$tTime = time();
		if(count($tRow) > 1){
			$tSql = 'update address set label = \'' .$pLabel. '\',ismine='.$pIsmine.' where address = \'' .$pAddress. '\' and mark =\''.$pWallet.'\'';
		}else{
			$tSql = 'insert into address(address,label,mark,created,ismine) values(\''.$pAddress.'\',\''.$pLabel.'\',\''.$pWallet.'\','.$tTime.','.$pIsmine.')';
		}
		return $this->sqlite('appdata')->query($tSql);
	}

	/**
	 * 得到address label
	 */
	private function getLabel($pAddress , $pWallet){
        $tRow = $this->sqlite('appdata')->getRow('select label from address where address = \'' .$pAddress. '\' and mark =\''.$pWallet.'\'');
		if(empty($tRow['label'])){
			return $this->lang->wallet->default_address;
		}
		return $tRow['label'];
	}
	private function checkaddress($pWallet,$tList,$tWalletinfo){
		//获取sqlite 的address 地址
		$tTime = time();
		$tSAddress = $this->sqlite('appdata')->getAll('select address from address where mark = \''.$pWallet.'\' and ismine  in(1,2)');
		$tSAddress = Tool_Fnc::array_column($tSAddress,'address');
		$tRpcaddress = Tool_Fnc::array_column($tList,'address');
		$tDiff = array_diff($tRpcaddress , $tSAddress);
			if(is_array($tDiff) && count($tDiff) > 0){

					foreach($tDiff as $tVal){
						$tCount = $this->sqlite('appdata')->getRow('select count(0) c from address where address=\''.$tVal.'\' and mark = \''.$pWallet.'\'');
						if(!$tIsminedata = $this->ismineaddress($tWalletinfo,$tVal)){continue;}

						$tIsmine = 1;#自己
						if($tIsminedata['ismine'] == false){
							$tIsmine = 2;
						}

						if(empty($tCount['c'])){
							$tData = array(
								'address' => $tVal,
								'label' => '',
								'mark' => $tIsminedata['symbol'],
								'ismine' => $tIsmine,
								'created' => $tTime,
							);
							$this->sqlite('appdata')->insert('address',$tData);
						}else{
							$this->sqlite('appdata')->query('update address set ismine = '. $tIsmine . ' where address = \''.$tVal.'\' and mark = \''.$pWallet.'\'');
						}
					}

				}
	}
	private function ismineaddress($tWalletinfo,$tAddress){
		$tData =Tool_Fnc::rpc_client($tWalletinfo)->validateaddress($tAddress);
		if(empty($tData['isvalid'])){return 0;}
		return $tData;

	}
	private function dosendmany($tWalletinfo,$pWallet,$pPayaddress,$pAddr_Amount)
	{
		$tTend = $pPayaddress;
		if($pWallet != 'ABC'){
			$tTend = $this->getTendaddress($tWalletinfo);
			$tTend = $tTend['address'];

			$tRes = Tool_Fnc::rpc_client($tWalletinfo)->sendmany($pPayaddress,$pAddr_Amount,$tTend,$tTend,$pPayaddress);
		}else{

			$tRes = Tool_Fnc::rpc_client($tWalletinfo)->sendmany($pPayaddress,$pAddr_Amount,$tTend,$tTend);
		}
		return $tRes;
	}
}
