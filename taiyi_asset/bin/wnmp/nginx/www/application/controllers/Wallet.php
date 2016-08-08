<?php
class WalletController extends Ctrl_Base{
    public function indexAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);		
		$tWalletinfo = $this->getwallet($pWallet);	

		$tGetinfo = $this->getwalletcoin($tWalletinfo);
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->assign('tAct' , '');
		$this->display('wallet/index');
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
			$tList = Tool_Fnc::rpc_client($tWalletinfo)->listreceivedbyaddress(0,true);
			#$tList = Tool_Fnc::rpc_client($tWalletinfo)->listaddresses();
			if(count($tList) && is_null($tList['error'])){

				$this->checkaddress($pWallet,$tList,$tWalletinfo);
				
				$tData = array();
				foreach($tList as $tkey => $tRow){
					$tIsmine = $this->sqlite('appdata')->getRow('select ismine,created from address where mark = \'' .$pWallet. '\' and address=\''.$tRow['address'].'\'');
					if(empty($tIsmine['ismine']) || $tIsmine['ismine'] != 1){
						continue;
					}
					$tRow['created'] = $tIsmine['created'];
					$tData[$tkey] = $tRow;
				}
				foreach($tData as $tKey=> $tRow){
					$tData[$tKey]['account'] =  empty($tRow['account'])?$this->lang->wallet->default_address:$tRow['account'];	
					$tLabel =  $this->getLabel($tRow['address'],$pWallet);
					if(!empty($tLabel)){
						$tData[$tKey]['account'] = $tLabel;	
					}
				}
				$tData = Tool_Fnc::arraySort($tData,'created');
				Tool_Fnc::ajaxMsg('' , 1 , $tData);
			}else{
				Tool_Fnc::ajaxMsg($this->lang->wallet->message3. empty($tList['error'])?'':$tList['error'] , 0);
			}

		}

		$this->assign('tWallet' , $pWallet);
		$this->assign('tAct' , 'listreceivedbyaddress');
		$this->display('wallet/listreceivedbyaddress');
	}
	private function checkaddress($pWallet,$tList,$tWalletinfo){
		//获取sqlite 的address 地址
				$tSAddress = $this->sqlite('appdata')->getAll('select address from address where mark = \''.$pWallet.'\' and ismine  in(1,2)');	
				$tSAddress = Tool_Fnc::array_column($tSAddress,'address');
				$tRpcaddress = Tool_Fnc::array_column($tList,'address');
				$tDiff = array_diff($tRpcaddress , $tSAddress);
				if(is_array($tDiff) && count($tDiff) > 0){
					$tPassphrase =Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase('zyr123',60);
					if(!is_null($tPassphrase['error']) && $tPassphrase['error']['code'] == '-17'){#已经解密
					}else{
						$tPasswallet = 0;
						if(!is_null($tPassphrase['error']) && $tPassphrase['error']['code'] == '-15'){
							$tPasswallet = 1;//钱包没有密码
						}
						if(empty($tPasswallet)){Tool_Fnc::ajaxMsg('' , -1 );}
					}
					foreach($tDiff as $tVal){
						$tCount = $this->sqlite('appdata')->getRow('select count(0) c from address where address=\''.$tVal.'\' and mark = \''.$pWallet.'\'');
						$tPKEY =Tool_Fnc::rpc_client($tWalletinfo)->dumpprivkey($tVal);
						if(is_array($tPKEY['error']) && $tPKEY['error']['code'] == '-102'){
							Tool_Fnc::ajaxMsg('pos解锁' , -102);
						}

						$tIsmine = 1;#自己
						if(is_array($tPKEY['error'])){
							$tIsmine = 2;	
						}

						if(empty($tCount['c'])){
							$tData = array(
								'address' => $tVal,
								'label' => '',
								'mark' => $pWallet,
								'ismine' => $tIsmine,
							);												
							$this->sqlite('appdata')->insert('address',$tData);
						}else{
							$this->sqlite('appdata')->query('update address set ismine = '. $tIsmine . ' where address = \''.$tVal.'\' and mark = \''.$pWallet.'\'');	
						}
					}
		
				}		
	}
	/**
	 * 添加地址
	 */
		
	public function listtransactionsAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);		
		$pBackdata = empty($_GET['backdata'])?'':trim($_GET['backdata']);		

		if(!empty($pBackdata)){

			if($tList = Cache_File::get('listtransactions:'.$pWallet,60)){
				Tool_Fnc::ajaxMsg('' , 1 , $tList);
			}
			$tWalletinfo = $this->getwallet($pWallet);	
			$tGetinfo = $this->getwalletcoin($tWalletinfo);
			#$tList = Tool_Fnc::rpc_client($tWalletinfo)->listtransactions('*',$tGetinfo['blocks'],0);
			if(!isset($tGetinfo['numberoftransactions'])){$tGetinfo['numberoftransactions']=300000000;}
			$tList = Tool_Fnc::rpc_client($tWalletinfo)->listtransactions('*',($tGetinfo['numberoftransactions']*3),0);
			
			$tDarr = array('receive' => $this->lang->main->receive , 'send' => $this->lang->main->send);
			if(count($tList) && is_null($tList['error'])){
				$tMList = new ListtransactionsModel;
				$tList = $tMList->listtransactions_exclude($tList);
				foreach($tList as $tKey => $tRow){
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

				$tData = $this->sqlite('appdata')->getAll('select label,address from address where mark = \''.$pWallet.'\'');
				$tAddresslabel = array();
				foreach($tData as $tRow){$tAddresslabel[$tRow['address']] = $tRow['label'];}
				foreach($tList as $tKey => $tRow){
					$tLabel = empty($tAddresslabel[$tRow['address']])?$this->lang->wallet->no_label:$tAddresslabel[$tRow['address']];
					$tList[$tKey]['label'] = $tLabel;
				}
				if(count($tList) && is_null($tList['error'])){
					Cache_File::set('listtransactions:'.$pWallet,$tList);
				}

				Tool_Fnc::ajaxMsg('' , 1 , $tList);
			}else{
				Tool_Fnc::ajaxMsg($this->lang->wallet->message4 . empty($tList['error'])?'':$tList['error'] , 0);
			}
		}

		$this->assign('tAct' , 'more');
		$this->assign('tWallet' , $pWallet);
		$this->display('wallet/listtransactions');
	}

	private function getwalletcoin($pWallet){
		$rpcWalletinfo = Tool_Fnc::rpc_client($pWallet)->getinfo();
		return $rpcWalletinfo;	
	}

	/**
	 * 发送
	 */
	public function sendtoaddressAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);		
		$pSent = empty($_GET['sent'])?0:$_GET['sent'];
		$pAccount = empty($_GET['account'])?'':trim($_GET['account']);
		$pAddress = empty($_GET['address'])?'':trim($_GET['address']);
		$pAmount = empty($_GET['amount'])?'':trim($_GET['amount']);

		$tWalletinfo = $this->getwallet($pWallet);	
		if($tWalletinfo['cfos']==1){echo '<script>location.href="/cfos_wallet/sendtoaddress?wallet='.$tWalletinfo['mark'].'&account='.urlencode($pAccount).'&address='.$pAddress.'&amount='.$pAmount.'";</script>';}
		if(empty($tWalletinfo)){
			$tWallet = strtoupper($pWallet);
			$tSql = 'select mark from mywallet where en_name =\''.$tWallet.'\'';
			$tWalletinfo = $this->sqlite()->getRow($tSql);
			if(!empty($tWalletinfo['mark'])){echo '<script>location.href="/wallet/sendtoaddress?wallet='.$tWalletinfo['mark'].'&account='.urlencode($pAccount).'&address='.$pAddress.'&amount='.$pAmount.'";</script>';}
			
		}

		if(!empty($pSent)){
			$pAddress = empty($_POST['address'])?'':trim($_POST['address']);
			$pAmount = empty($_POST['amount'])?0:floatval(trim($_POST['amount']));
			$pAccount = empty($_POST['account'])?'':trim($_POST['account']);
			$pPassword = empty($_POST['password'])?'':trim($_POST['password']);

			if(empty($pAddress) || empty($pAmount)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->index_error , 0);
			}		
			if(!empty($pAccount) && !Tool_Validate::safe($pAccount)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->message7 , 0);
			}
			#if(empty($tPasswallet) && empty($pPassword)){
			#	Tool_Fnc::ajaxMsg('密码不能为空' , 0);
			#}
			/*if(!empty($pPassword)){
				$tPassphrase =Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase($pPassword,60);
				if(!is_null($tPassphrase['error'])){
					Tool_Fnc::ajaxMsg($tPassphrase['error']['message'].' 001' , 0);
				}
			}	
			*/
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

			$txid = Tool_Fnc::rpc_client($tWalletinfo)->sendtoaddress($pAddress , $pAmount);

			Tool_Fnc::rpc_client($tWalletinfo)->walletlock();
			//判断是否开启pos
			$tIspos = $this->sqlite()->getRow('select count(*) c from mywallet where mark = \''.$pWallet.'\' and pos = 1 and unlock = 1');
			if(!empty($tIspos['c'])){
				Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase($pPassword , 30758400 , true);
			}

			if(empty($txid) || (is_array($txid) && !is_null($txid['error']))){
				if($txid['error']['code'] == '-4' && $this->l == 'cn'){
					$tError = $this->lang->wallet->insufficient_funds;	
				}elseif($txid['error']['code'] == '-13' && $this->l == 'cn'){
					$tError = $this->lang->wallet->message12;	
				}else{
					$tError = $txid['error']['message'];	
				}
				Tool_Fnc::ajaxMsg($this->lang->wallet->sending_coin_failed.':' . $tError , 0);
			}

			//修改标签
			$tLA = Tool_Fnc::rpc_client($tWalletinfo)->listaddresses();
			$tIsA = 0;
			foreach($tLA as $tRow){
				if($tRow['address'] == $pAddress){
					$tIsA = 1;	
				}			
			}

			$tIsmine = 2;
			if($tIsminedata = $this->ismineaddress($tWalletinfo,$pAddress)){
				if($tIsminedata['ismine'] == true){$tIsmine = 1;}
			}
			if(empty($tIsA)){
				//Tool_Fnc::rpc_client($tWalletinfo)->setaccount($pAddress,$pAccount); 
				Tool_Fnc::rpc_client($tWalletinfo)->setaccount($pAddress); 
				$this->address($pAccount,$pAddress,$pWallet, $tIsmine);
			}else{
				if(!empty($pAccount)){
					#Tool_Fnc::rpc_client($tWalletinfo)->editaccount($pAddress,$pAccount); 
					$this->address($pAccount,$pAddress,$pWallet , $tIsmine);
				}	
			}

			Cache_File::set('listtransactions:'.$pWallet,'');
			Cache_File::set('walletinfo:'.$pWallet,'');
			Tool_Fnc::ajaxMsg($this->lang->wallet->sending_coin_successful, 1 , array('txid' => $txid));
		}else{
				
			$tPassphrase =Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase('zyr123',60);
			$tPasswallet = 0;
			if(!is_null($tPassphrase['error']) && $tPassphrase['error']['code'] == '-15'){
				$tPasswallet = 1;//钱包没有密码
			}
		
		}

		$tGetinfo = $this->getwalletcoin($tWalletinfo);
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('pAmount' , $pAmount);
		$this->assign('pAccount' , $pAccount);
		$this->assign('pAddress' , $pAddress);
		$this->assign('tPasswallet' , $tPasswallet);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->assign('tAct' , 'sendtoaddress');
		$this->display('wallet/sendtoaddress');
	}

	/**
	 * 添加接受地址
	 */
	public function listreceivedbyaddressaddAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletinfo = $this->getwallet($pWallet);	

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

			//$tRes = Tool_Fnc::rpc_client($tWalletinfo)->getnewaddress($pAccount);
			$tRes = Tool_Fnc::rpc_client($tWalletinfo)->getnewaddress();

			if(empty($tRes) || (!is_null($tRes['error']) && is_array($tRes))){
				Tool_Fnc::ajaxMsg($tRes['error']['message'] , 0);
			}
			$this->address($pAccount,$tRes,$pWallet ,1);
			Tool_Fnc::ajaxMsg($this->lang->wallet->added_successfully , 1);
		}

		$tGetinfo = $this->getwalletcoin($tWalletinfo);
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tAct' , 'more');
		$this->assign('tWallet' , $pWallet);
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->display('wallet/listreceivedbyaddressadd');
	}

	/**
	 * 地址薄添加 
	 */
	public function sendaddressaddAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletinfo = $this->getwallet($pWallet);	

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
			#$tLA = Tool_Fnc::rpc_client($tWalletinfo)->listaddresses();
			$tLA = Tool_Fnc::rpc_client($tWalletinfo)->listreceivedbyaddress(0,true);
			foreach($tLA as $tRow){
				if($tRow['address'] == $pAddress){
					Tool_Fnc::ajaxMsg($this->lang->wallet->the_address_has_been_added , 0);
				}			
			}

			//$tRes = Tool_Fnc::rpc_client($tWalletinfo)->setaccount($pAddress , $pAccount);	
			$tRes = Tool_Fnc::rpc_client($tWalletinfo)->setaccount($pAddress);	
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
			Tool_Fnc::ajaxMsg($this->lang->wallet->added_successfully , 1);
		}
		$tGetinfo = $this->getwalletcoin($tWalletinfo);
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tAct' , 'more');
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->display('wallet/sendaddressadd');
	}
	public function sendaddresseditAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletinfo = $this->getwallet($pWallet);	
		$pAddress = empty($_GET['address'])?'':trim($_GET['address']);
		$pAccount = empty($_GET['account'])?'':trim($_GET['account']);
		$pMysubmit = empty($_GET['mysubmit'])?'':trim($_GET['mysubmit']);


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

			$this->address($pAccount,$pAddress,$pWallet,2);

			if((!is_null($tRes['error']) && is_array($tRes))){
				Tool_Fnc::ajaxMsg($tRes['error']['message'] , 0);
			}
			Tool_Fnc::ajaxMsg($this->lang->wallet->changed_successfully , 1);
		}
		$this->assign('tAddress' , $pAddress);
		$this->assign('tAccount' , $pAccount);
		$tGetinfo = $this->getwalletcoin($tWalletinfo);
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tAct' , 'more');
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->display('wallet/sendaddressedit');
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

			$this->address($pAccount,$pAddress,$pWallet,1);
			Tool_Fnc::ajaxMsg($this->lang->wallet->changed_successfully , 1);
		}

		$this->assign('tAddress' , $pAddress);
		$this->assign('tAccount' , $pAccount);
		$tGetinfo = $this->getwalletcoin($tWalletinfo);
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tAct' , 'more');
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->display('wallet/listreceivedbyaddressedit');
	}

	public function moreAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);		
		$tWalletinfo = $this->getwallet($pWallet);	

		$tGetinfo = $this->getwalletcoin($tWalletinfo);
		$this->assign('tWalletinfo' , $tWalletinfo);
		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tAct' , 'more');
		$this->display('wallet/more');
	}


	/**
	 * 发送地址
	 */
	function sendaddressAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);		

/*		$tList = Tool_Fnc::rpc_client($tWalletinfo)->listaddresses();
		foreach($tList as $tKey => $tRow){
			$tGetlabel = $this->getLabel($tRow['address'],$pWallet);				
			if(!empty($tGetlabel)){
				$tList[$tKey]['account'] = $tGetlabel;		
			}
		}
	*/
		$pBackdata = empty($_GET['backdata'])?'':trim($_GET['backdata']);		
		$tWalletinfo = $this->getwallet($pWallet);	
		if(!empty($pBackdata)){
			$tList = Tool_Fnc::rpc_client($tWalletinfo)->listreceivedbyaddress(0,true);
			if(count($tList) && is_null($tList['error'])){

				$this->checkaddress($pWallet,$tList,$tWalletinfo);
				
				$tData = array();

				$tIsmine = $this->sqlite('appdata')->getAll('select ismine,address,label,created from address where mark = \'' .$pWallet. '\'');
				$tIsminearray = array();
				foreach($tIsmine as $tRow){$tIsminearray[$tRow['address']] = array('ismine' => $tRow['ismine'],'account' => $tRow['label'],'created'=>$tRow['created']);}
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
				Tool_Fnc::ajaxMsg('' , 1 , $tData);
			}	
		}

		$tGetinfo = $this->getwalletcoin($tWalletinfo);

		$this->assign('tGetinfo' , $tGetinfo);
		$this->assign('tWallet' , $pWallet);
		$this->assign('tWalletinfo' , $tWalletinfo);
		//$this->assign('tList' , $tList);
		$this->assign('tAct' , 'more');
		$this->display('wallet/sendaddress');
	}
	public function walletpassphraseAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);		


		if(!empty($_GET['submit'])){
			#Tool_Fnc::ajaxMsg('测试' , 0);	
			$pPassword = empty($_POST['password'])?"":$_POST['password'];
			if(empty($pPassword)){Tool_Fnc::ajaxMsg('钱包密码不能为空' , 0);}
			$tWalletinfo = $this->getwallet($pWallet);	
			$tPassphrase = Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase($pPassword,20);
			if(!is_null($tPassphrase['error']) && $tPassphrase['error']['code'] != '-17'){
				$tError = '';
				if($tPassphrase['error']['code'] == '-14' && $this->l =='cn'){
					$tError = $this->lang->wallet->message11;	
				}else{
					$tError = $tPassphrase['error']['message'];			
				}
				Tool_Fnc::ajaxMsg($tError , 0);
			}
			Tool_Fnc::ajaxMsg('ok' , 1);
			exit;
		}

		$this->assign('tWallet' ,$pWallet);
		$this->display('wallet/walletpassphrase');
	}

	/**
	 * 操作 address 表
	 */
	private function address($pLabel , $pAddress , $pWallet,$pIsmine){
		
        $tRow =$this->sqlite('appdata')->getRow('select * from address where address = \'' .$pAddress. '\' and mark =\''.$pWallet.'\'');        
		$tSql = '';
		$tTime = time();
		if(count($tRow) > 1){
			$tSql = 'update address set label = \'' .$pLabel. '\',ismine='.$pIsmine.' where address = \'' .$pAddress. '\' and mark =\''.$pWallet.'\'';		
		}else{
			$tSql = 'insert into address(address,label,mark,ismine,created) values(\''.$pAddress.'\',\''.$pLabel.'\',\''.$pWallet.'\','.$pIsmine.','.$tTime.')';		
		}
		return $this->sqlite('appdata')->query($tSql);
	}

	/**
	 * 得到address label
	 */
	private function getLabel($pAddress , $pWallet){
        $tRow = $this->sqlite('appdata')->getRow('select label from address where address = \'' .$pAddress. '\' and mark =\''.$pWallet.'\'');        
		if(empty($tRow['label'])){
			return '';	
		}
		return $tRow['label'];
	}
	private function ismineaddress($tWalletinfo,$tAddress){
		$tData =Tool_Fnc::rpc_client($tWalletinfo)->validateaddress($tAddress);
		if(empty($tData['isvalid'])){return 0;}
		return $tData;
		
	}
}
