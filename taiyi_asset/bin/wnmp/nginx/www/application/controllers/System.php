<?php
class SystemController extends Ctrl_Base{
    public function indexAction(){

		$tWalletrunpasswd = $this->walletrunpasswd();
	
		$tExportdir = str_replace('\\' , '/' , USER_DIR . '/backupwallet');			
		if(!is_dir($tExportdir)){
			Tool_Fnc::make_dir($tExportdir);		
		}

		//版本
		$tSetting = SOFT_DIR . '/Setting.ini';	
		$tSettingdata = Tool_Fnc::readfile($tSetting);
		$tSettingarr = explode("\r\n" , $tSettingdata);
		$tVersion = array();
		if(count($tSettingarr) > 1){
			foreach($tSettingarr as $tVal){
				if(substr($tVal,0,13) != 'VersionString'){
					continue;	
				}
				$tVersion = explode('=' , $tVal);
			}	
		}

		//获取pos利息币
		$tPosstatus = $this->posstatus();
		$tPoslist = $this->sqlite()->getAll('select name,cfos,mark,unlock,en_name from mywallet where status = 3 and pos = 1');
		#$tPosunlockcount = $this->sqlite()->getRow('select count(*) c from mywallet where status = 3 and pos = 1 and unlock=1');
		foreach($tPoslist as $tKey => $tRow){
			$tIs = 0;
			foreach($tWalletrunpasswd['passwd'] as $tRowpwd){
				if($tRow['mark'] == $tRowpwd['mark']){
					$tIs = 1;
					break;
				}	
			}	
			$tMark = $tRow['mark'];
			if($tRow['cfos'] == 1){$tMark = 'cfos';}
			if(!in_array($tMark,$tPosstatus) || empty($tIs)){unset($tPoslist[$tKey]);continue;}
		}

		//所有的钱包
		//该数据主要 钱包备份在用 
		$tWalletlist = $this->sqlite()->getAll('select name,mark,en_name,cfos,pos from mywallet where status = 3 and cfos <> 1');
		$tWalletlist_cfos = $this->sqlite()->getRow('select name,mark,en_name,cfos,pos from mywallet where status = 3 and cfos = 1 limit 1');
		if(count($tWalletlist_cfos) > 1){$tWalletlist_cfos['name'] = '太一资产';$tWalletlist_cfos['en_name'] = 'TaiYi Asset';$tWalletlist[] = $tWalletlist_cfos;}

		//POS钱包在线
		$tPosrunlist = array();
		foreach($tWalletlist as $tRow){
			if(empty($tRow['pos'])){continue;}
			foreach($tWalletrunpasswd as $tWRow){
				foreach($tWRow as $tR){
					if($tR['mark'] == $tRow['mark']){
						$tPosrunlist[] = $tRow;	
					}
				}	
			}	
		}




		$this->assign('tPosrunlist' , $tPosrunlist);
		$this->assign('tPosunlockcount' , $tPosunlockcount);
		$this->assign('tPosstatus' , $tPosstatus);
		$this->assign('tWalletlist' , $tWalletlist);
		$this->assign('tPoslist' , $tPoslist);
		$this->assign('tHeadact' , 'system');
		$this->assign('tVersion' , empty($tVersion[1])?'V1.0.0':$tVersion[1]);
		$this->assign('tWalletrunpasswd' , $tWalletrunpasswd);
		$this->assign('tExportdir' , iconv('gbk' , 'utf-8',$tExportdir));
		$this->display('system/index');
    }
	/**
	 * POS 状态
	 */
	private function posstatus(){
		$tRunxml = file_get_contents(USER_DIR . '/conf.xml');
		$tRunxml = new SimpleXMLElement($tRunxml);
		$tData = array();
		foreach ($tRunxml->service_list->children() as $child){
			if(!isset($child->arg)) continue;
			if(!preg_match('/-genpos=1/',$child->arg)){
				continue;
			}
			$tData[] = $child->name;
		}
		return $tData;
	}
	/**
	 * 判断钱包 启动，是否加过密码
	 */
	private function walletrunpasswd(){
		$tWalletdata = $this->sqlite()->getAll('select * from mywallet where status = 3 and cfos <> 1');
		$tData = array('nopasswd','passwd');
		foreach($tWalletdata as $tRow){
			$tPassphrase =Tool_Fnc::rpc_client($tRow)->walletpassphrase('zyr123',60);
			if(!is_null($tPassphrase['error']) && ($tPassphrase['error']['code'] == '-15')){
				//钱包没有密码
				$tData['nopasswd'][] = $tRow;
			}elseif(!is_null($tPassphrase['error']) && ($tPassphrase['error']['code'] == '000')){}
			else{
				//钱包有密码
				$tData['passwd'][] = $tRow;
			}
		}

		$tWalletdata = $this->sqlite()->getAll('select * from mywallet where status = 3 and cfos = 1 limit 1');
		foreach($tWalletdata as $tRow){
			$tPassphrase =Tool_Fnc::rpc_client($tRow)->walletpassphrase('zyr123',60);
			$tRow['name'] = '太一资产';
			$tRow['en_name'] = 'TaiYi Asset';
			if(!is_null($tPassphrase['error']) && ($tPassphrase['error']['code'] == '-15')){
				//钱包没有密码
				$tData['nopasswd'][] = $tRow;
			}elseif(!is_null($tPassphrase['error']) && ($tPassphrase['error']['code'] == '000')){}
			else{
				//钱包有密码
				$tData['passwd'][] = $tRow;
			}
		}
		return $tData;
	}

	/**
	 * 加密钱包
	 */
	public function encryptAction(){
		
		if(count($_POST)){
			$pPassword = empty($_POST['password'])?'':trim($_POST['password']);	
			$pRepeatpassword = empty($_POST['repeatpassword'])?'':trim($_POST['repeatpassword']);	
			$pWallets = $_POST['wallets'];	
		
			if(!count($pWallets)){
				Tool_Fnc::ajaxMsg($this->lang->system->please_select_coin , 0);	
			}
			if(empty($pPassword) || empty($pRepeatpassword)){
				Tool_Fnc::ajaxMsg($this->lang->system->password_for_encrypted_wallet , 0);	
			}

			if($pPassword != $pRepeatpassword){
				Tool_Fnc::ajaxMsg($this->lang->system->message2, 0);	
			}


			$tMsg = '';
			$tPasswdSuccess = array();
			foreach($pWallets as $tVal){
				$tWalletrow = $this->sqlite()->getRow('select * from mywallet where status = 3 and mark = \''.$tVal.'\'');
				$tRes = Tool_Fnc::rpc_client($tWalletrow)->encryptwallet($pPassword);

				if($tWalletrow['cfos'] == 1){$tWalletrow['name'] = '太一资产';$tWalletrow['en_name'] = 'TaiYi Asset';}
				if($this->l == 'en'){$tWalletrow['name'] = $tWalletrow['en_name'];}

				$tMsg .= $tWalletrow['name'].':';
				if(!is_null($tRes['error'])&&is_array($tRes)){
					if($tRes['error']['code'] == '-14' && $this->l =='cn'){
						$tMsg .= $this->lang->wallet->message11;	
					}else{
						$tMsg .= $tRes['error']['message'];			
					}
				}else{
					if($tWalletrow['cfos'] == 1){$tVal = 'cfos';}
					$tPasswdSuccess[] = $tVal;
					$tMsg .= $this->lang->system->message3;
				}
				unset($tRes);
				$tMsg .= '<br />';
			}

			Tool_Fnc::ajaxMsg($tMsg , 1,implode('|',$tPasswdSuccess));	
		}
		exit;
	}
	/**
	 * 解锁
	 */
	public function unlockAction(){
		
		$pWallets = empty($_POST['wallets'])?'':$_POST['wallets'];
		$pPassword = empty($_POST['password'])?'':$_POST['password'];
		if(!count($pWallets) || empty($pWallets)){Tool_Fnc::ajaxMsg($this->lang->system->message4);}
		if(empty($pPassword)){Tool_Fnc::ajaxMsg($this->lang->system->encrypted_wallet_password,0);}
		$tMsg = '';

		#$this->sqlite()->getRow('update mywallet set unlock=0 where status=3');	
		foreach($pWallets as $tV){
			$tWalletinfo = $this->sqlite()->getRow('select * from mywallet where status=3 and mark = \''.$tV.'\'');	
			$tRes = Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase($pPassword , 30758400 , true);
			if($this->l == 'en'){
				$tWalletinfo['name'] = $tWalletinfo['en_name'];
			}
			if(is_array($tRes['error'])){
				$tError = '';
				if($tRes['error']['code'] == '-14' && $this->l =='cn'){
					$tError = $this->lang->wallet->message11;	
				}elseif($tRes['error']['code'] == '-17' && $this->l =='cn'){
					$tError = $this->lang->system->message11;	

				}else{
					$tError = $tRes['error']['message'];			
				}

				$tMsg .= $tWalletinfo['name'] . '  ' . $tError . "<br />" ;
			}else{
				$this->sqlite()->getRow('update mywallet set unlock=1 where status=3 and mark = \''.$tV.'\'');	
				$tMsg .= $tWalletinfo['name'] . ' '.$this->lang->system->unlock_successful.'！'. "<br />";
			}
		}
		Tool_Fnc::ajaxMsg($tMsg , 1);	
		exit;
	}

	/**
	 * 备份钱包
	 */
	public function backupwalletAction(){
		if(!count($_POST)){exit;}
		$pWallets = $_POST['wallets'];	
		
		if(!count($pWallets)){
			Tool_Fnc::ajaxMsg($this->lang->system->please_select_coin , 0);	
		}

		$tHomepath = exec('echo %APPDATA%');
		$tHomepath = $tHomepath . '/taiyi';

		$tWherein = '';
		foreach($pWallets as $tVal){
			$tWherein .= '\''.$tVal.'\',';	
		}
		$tWherein = trim($tWherein,',');
		$tWalletdata = $this->sqlite()->getAll('select * from mywallet where status = 3 and mark in ('.$tWherein.')');
	
		$tStr = '';
		foreach($tWalletdata as $tRow){
			if(empty($tRow['mark'])){continue;}
			$tGetinfo = Tool_Fnc::rpc_client($tRow)->getinfo();
			if($this->l == 'en'){
				$tRow['name'] = $tRow['en_name'];
			}
			if($tRow['cfos'] == 1){$tRow['name'] = '太一资产';$tRow['en_name'] = 'TaiYi Asset';$tRow['mark'] = 'abcoin';}

			$tWalletfile = $tHomepath . '/' . $tRow['mark'] . '/wallet.dat';
			if(!empty($tGetinfo['testnet'])){
				$tWalletfile = $tHomepath . '/' . $tRow['mark'] . '/testnet/wallet.dat';
			}

			if(!is_file($tWalletfile)){$tStr .= $tRow['name'] . "--".$this->lang->system->message5."\r\n";continue;}	
			$pBackupwalletfile = USER_DIR.'/backupwallet/wallet_'.$tRow['mark'].'_'.date('YmdHis').'.dat';

			if(!copy($tWalletfile,$pBackupwalletfile)){
				$tStr .= $tRow['name'] . "--".$this->lang->system->message6."001<br />";
				continue;	
			}
			if(filesize($tWalletfile) != filesize($pBackupwalletfile)){
				$tStr .= $tRow['name'] . "--".$this->lang->system->message6."002<br />";
				unlink($pBackupwalletfile);
				continue;	
			}
			$tStr .= $tRow['name'] . "--".$this->lang->system->succeed."<br />";

		}
		

		Tool_Fnc::ajaxMsg($tStr, 1);	
		exit;
	}

	/**
	 * 导入私钥
	 */
	public function importprivkeyAction(){
		if(!count($_POST)){exit;}
		$pPrivkey = empty($_POST['privkey'])?'':trim($_POST['privkey']);	
		$pPassword = empty($_POST['password'])?'':trim($_POST['password']);	
		$pHipasswd = empty($_POST['hipasswd'])?'':trim($_POST['hipasswd']);	
		$pWallet = empty($_POST['wallet'])?'':trim($_POST['wallet']);	
		
		if(!empty($pHipasswd)){
			if(empty($pPassword)){
				Tool_Fnc::ajaxMsg($this->lang->wallet->message12 , 0);	
			}		
		}

		if(empty($pWallet)){
			Tool_Fnc::ajaxMsg($this->lang->system->please_select_coin , 0);	
		}
		if(empty($pPrivkey)){
			Tool_Fnc::ajaxMsg($this->lang->system->private_key_cannot_be_empty , 0);	
		}

		$tWalletrow = $this->sqlite()->getRow('select * from mywallet where status = 3 and mark = \''.$pWallet.'\'');
		if(!empty($pPassword) && !empty($pHipasswd)){
			$tPassphrase =Tool_Fnc::rpc_client($tWalletrow)->walletpassphrase($pPassword,60);
			if(!is_null($tPassphrase['error']) && $tPassphrase['error']['code'] != '-17'){
				Tool_Fnc::ajaxMsg($tPassphrase['error']['message'] , 0);	
			}		
		}

		$tRes = Tool_Fnc::rpc_client($tWalletrow)->importprivkey($pPrivkey);
		if(!is_null($tRes['error']) && is_array($tRes)){
			Tool_Fnc::ajaxMsg($tRes['error']['message'] , 0);	
		}
		Tool_Fnc::ajaxMsg($this->lang->system->imported_successfully , 1);	
	}

	/**
	 * 修改加密钱包密码
	 */
	public function passphrasechangeAction(){
		if(count($_POST)){
			$pOldpassword = empty($_POST['oldpassword'])?'':trim($_POST['oldpassword']);	
			$pPassword = empty($_POST['password'])?'':trim($_POST['password']);	
			$pRepeatpassword = empty($_POST['repeatpassword'])?'':trim($_POST['repeatpassword']);	
			$pWallets = $_POST['wallets'];	
		
			if(!count($pWallets)){
				Tool_Fnc::ajaxMsg($this->lang->system->please_select_coin , 0);	
			}

			if(empty($pPassword) || empty($pRepeatpassword) || empty($pOldpassword)){
				Tool_Fnc::ajaxMsg($this->lang->system->encrypted_wallet_password , 0);	
			}

			if($pPassword != $pRepeatpassword){
				Tool_Fnc::ajaxMsg($this->lang->system->message7 , 0);	
			}


			$tMsg = '';
			$tPasswdSuccess = array();
			foreach($pWallets as $tVal){
				$tWalletrow = $this->sqlite()->getRow('select * from mywallet where status = 3 and mark = \''.$tVal.'\'');
				$tRes = Tool_Fnc::rpc_client($tWalletrow)->walletpassphrasechange($pOldpassword,$pPassword);
				if($tWalletrow['cfos'] == 1){$tWalletrow['name'] = '太一资产';$tWalletrow['en_name'] = 'TaiYi Asset';}
				if($this->l == 'en'){$tWalletrow['name'] = $tWalletrow['en_name'];}

				$tMsg .= $tWalletrow['name'].':';

				if(!is_null($tRes['error']) && is_array($tRes)){
					if($tRes['error']['code'] == '-14' && $this->l =='cn'){
						$tMsg .= $this->lang->wallet->message11;	
					}else{
						$tMsg .= $tRes['error']['message'];			
					}
				}else{

					if($tWalletrow['cfos'] == 1){$tVal = 'cfos';}
					$tPasswdSuccess[] = $tVal;
					#Tool_Fnc::ajaxMsg($this->lang->system->message8 , 1);	
					$tMsg .= $this->lang->system->message8;
				}
				unset($tRes);
				$tMsg .= '<br />';
			}
			Tool_Fnc::ajaxMsg($tMsg , 1,implode('|',$tPasswdSuccess));	
		}
		exit;
		
	}
	public function stopwalletAction(){
		$tWalletlist = empty($_GET['walletlist'])?'':trim($_GET['walletlist']);	
		if(empty($tWalletlist)){exit;}

		$tWalletlist = explode('|', $tWalletlist);
		foreach($tWalletlist as $tKey => $tVal){
			$this->rebootWallet('unload',$tVal);
		}
		exit;
	}

	public function startwalletAction(){
		$tWalletlist = empty($_GET['walletlist'])?'':trim($_GET['walletlist']);	
		if(empty($tWalletlist)){exit;}
		$tWalletlist = explode('|', $tWalletlist);

		foreach($tWalletlist as $tKey => $tVal){
			$this->rebootWallet('load',$tVal);
		}
		exit;
	}
	private function rebootWallet($tAct,$tWallet){
		$tDatataiyi = array(
			'rpcport' => 9490,
			'rpcuser' => 'yueru',
			'rpcpassword' => 'yueru'
		);
		$tRes = Tool_Fnc::rpc_client($tDatataiyi)->wallet($tAct,$tWallet);
		if($tRes){
			$this->sqlite()->query('update mywallet set unlock=0 where mark = \''.$tWallet.'\'');
		}
		unset($tRes);
			
	}
	/**
	 * 语言切换
	 */
	public function langAction(){
		$pL = empty($_GET['l'])?'cn':trim($_GET['l']);
		$this->sqlite()->getRow('update lang set lang=\''.$pL.'\'');
		Tool_Fnc::ajaxMsg('' , 1);	
	}
	public function alertposAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$pType = empty($_GET['type'])?'open':trim($_GET['type']);
		if(empty($pWallet)){exit;}
		$tRunxml = file_get_contents(USER_DIR . '/conf.xml');
		$tRunxml = new SimpleXMLElement($tRunxml);
		$tData = array();
		foreach ($tRunxml->service_list->children() as $child){
			if($child->name != $pWallet){continue;}
			if(!isset($child->arg) && $pType == 'open'){
				$child->addChild('arg',' -genpos=1');
				break;
			}
			$tArg = clone $child->arg;
			unset($child->arg);
			$tArg = str_replace(' -genpos=1','',$tArg);
			$tArg = str_replace(' -genpos=0','',$tArg);
			if($pType == 'open'){
				$child->addChild('arg' , $tArg . ' -genpos=1');	
			}else{
				$child->addChild('arg' , $tArg . ' -genpos=0');	
			}
			break;
		}

		$tRunxmlstr = $tRunxml->asXML();
		Tool_Fnc::writefile(USER_DIR . '/conf.xml',$tRunxmlstr);
		$this->assign('tWallet' , $pWallet);
		$this->display('system/alertpos');
	}
}
