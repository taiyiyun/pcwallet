<?php
class Cfos_MywalletController extends Ctrl_Base{

//cfossyncinfoAction()  check the state of cfos if cfos is synced, by return the percent of cfos gives when syncing.
	public function cfossyncinfoAction(){
		$tSqlite = Tool_Fnc::sqlite_cfossyncinfo();
		$percent = $tSqlite->getRow('select * from updateinfo where cmd=\'getinfo\'');
		$tSqlite->close();
		if(!$percent||empty($percent)){Tool_Fnc::ajaxMsg("cfoswallet.db err" , 2, '');}
		$tData = array();
		$tData['percent']=$percent['value'];
		$tData['isremote']=$percent['isremote'];
		Tool_Fnc::ajaxMsg('',0,$tData);
	}
//balanceAction() 通过listaddressgroupings获取balance, 
//返回0, 正确
//1: 不可用
//2：执行出错
//3：为空
    public function balanceAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		if(empty($pWallet)  ){Tool_Fnc::ajaxMsg($this->lang->mywallet->wallet_index_cannot_be_empty , 2,'');}

    	$tSqlite = Tool_Fnc::sqlite();
		$tMywallet = $tSqlite->getAll('select name,en_name,mark,status,rpcuser,rpcpassword,rpcport,img,small_img from mywallet where cfos=1');
		$tSqlite->close();
		if(empty($tMywallet) || !count($tMywallet)){
			Tool_Fnc::ajaxMsg($this->lang->mywallet->the_wallet_is_not_founded , 2, '' );
		}

		$tCoinsinfo = Tool_Fnc::rpc_client($tMywallet[0])->listaddressgroupings();
		if(isset($tCoinsinfo['error']) && !empty($tCoinsinfo['error'])){
			if($tCoinsinfo['error']['code'] == '-32601'){
				//方法不可用
				Tool_Fnc::ajaxMsg( $this->lang->mywallet->acquiring_wallet_data_abnormal.'no method' , 1 , $tCoinsinfo['error'] );
			}else{
				//执行错误
				Tool_Fnc::ajaxMsg( $this->lang->mywallet->acquiring_wallet_data_abnormal.'listaddressgroupings' , 2 , $tCoinsinfo['error'] );
			}
		}
		if(empty($tCoinsinfo)){
			//返回空,可能同步。
			Tool_Fnc::ajaxMsg( 'listaddressgroupings return empty' , 3 , $tCoinsinfo );
		}
		$tBalance = 0;
		foreach($tCoinsinfo as $tRows){
			foreach((array)$tRows as $tRow){
				if($tRow["symbol"] != $pWallet){break;}	
				$tBalance += $tRow["amount"];
			}
		}

		$tData = Tool_Fnc::etonumber($tBalance);

		Tool_Fnc::ajaxMsg('',0,$tData);
	}
	public function walletinfoAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$pOnecfos = empty($_GET['onecfos'])?'0':trim($_GET['onecfos']);
		if($pOnecfos == 'undefined'){$pOnecfos=1;}

		if(empty($pOnecfos)){$tValid = 3600;}else{$tValid = 20;}
		if(empty($pWallet)  ){Tool_Fnc::ajaxMsg($this->lang->mywallet->wallet_index_cannot_be_empty , '0');}

    	$tSqlite = Tool_Fnc::sqlite();
		$tMywallet = $tSqlite->getAll('select name,en_name,mark,status,rpcuser,rpcpassword,rpcport,img,small_img from mywallet where cfos=1');
		$tSqlite->close();
		if(empty($tMywallet) || !count($tMywallet)){
			Tool_Fnc::ajaxMsg($this->lang->mywallet->the_wallet_is_not_founded , '0' );
		}
		$tCoinsinfo = Cache_File::get('getinfo',$tValid);
		if(empty($tCoinsinfo)){
			$tCoinsinfo = Tool_Fnc::rpc_client($tMywallet[0])->getinfo();
			if( !empty($tCoinsinfo['errors']) && !is_null($tCoinsinfo['errors'])){
				Cache_File::set('listaddressgroupings' , '');
				Cache_File::set('getinfo' , '');
				Cache_File::set('getpeerinfo' , '');
				Tool_Fnc::ajaxMsg( $this->lang->mywallet->acquiring_wallet_data_abnormal.'getinfo'.$tCoinsinfo['errors'] , '0' , $tCoinsinfo['errors'] );
			}
			Cache_File::set('getinfo' , $tCoinsinfo);
		}
		$tData = array();
		$tData['blocks'] = empty($tCoinsinfo['blocks'])?-1:$tCoinsinfo['blocks'];
		if($tData['blocks'] < 0){Tool_Fnc::ajaxMsg( ($tCoinsinfo), '0' , 'no block' );}

		$tCoinsinfo = Cache_File::get('listaddressgroupings',$tValid);
		if($pOnecfos == 1 && !$tCoinsinfo){
			$tCoinsinfo = Tool_Fnc::rpc_client($tMywallet[0])->listaddressgroupings();
			if(!empty($tCoinsinfo['error']) &&!is_null($tCoinsinfo['error'])  && $tCoinsinfo['error']['code'] != '-32601'){
				Tool_Fnc::ajaxMsg( $this->lang->mywallet->acquiring_wallet_data_abnormal.'list' , '0' , $tCoinsinfo['error'] );}
			Cache_File::set('listaddressgroupings' , $tCoinsinfo);
		}

		$tBalance = 0;
		foreach($tCoinsinfo as $tRows){
			foreach((array)$tRows as $tRow){
				if($tRow["symbol"] != $pWallet){break;}	
				$tBalance += $tRow["amount"];
			}
		}

		$tData['balance'] = Tool_Fnc::etonumber($tBalance);

		if(!$tPeerinfo = Cache_File::get('getpeerinfo',$tValid)){
			$tPeerinfo = Tool_Fnc::rpc_client($tMywallet[0])->getpeerinfo();
			Cache_File::set('getpeerinfo' , $tPeerinfo);
		}

		$tPeerheight = array();
		if(empty($tPeerinfo)){Tool_Fnc::ajaxMsg( '' , '0' , 'peer none');}
		foreach($tPeerinfo as $tRow){
			if(!isset($tRow['height'])){$tRow['height'] = $tRow['startingheight']; }
			$tPeerheight[] = $tRow['height'];	
		}
		sort($tPeerheight);
		$tData['height'] = empty($tPeerheight)?500:$tPeerheight[floor(count($tPeerheight)/2)];	
		Tool_Fnc::ajaxMsg('',1,$tData);
	}
	public function walletAction(){		
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		if(empty($pWallet)){Tool_Fnc::ajaxMsg($this->lang->mywallet->wallet_index_cannot_be_empty , '0');}
		$tSqlite = Tool_Fnc::sqlite();
		$tMywallet = $tSqlite->getRow('select * from mywallet where mark=\''.$pWallet.'\'');
		$tSqlite->close();
		if(empty($tMywallet) || !count($tMywallet)){Tool_Fnc::ajaxMsg($this->lang->mywallet->the_wallet_is_not_founded , '0' );}

		if(!$tCoinsinfo = Cache_File::get('getinfo',20)){
			$tCoinsinfo = Tool_Fnc::rpc_client($tMywallet)->getinfo();
			if( !empty($tCoinsinfo['errors'])){Tool_Fnc::ajaxMsg( $this->lang->mywallet->acquiring_wallet_data_abnormal." getinfo" , '0' , $tCoinsinfo['errors'] );}
			Cache_File::set('getinfo' , $tCoinsinfo);
		}


		$tMywallet['unconfirmed'] = empty($tCoinsinfo['unconfirmed'])?0:$tCoinsinfo['unconfirmed'];
		$tMywallet['stake'] = empty($tCoinsinfo['stake'])?0:$tCoinsinfo['stake'];#分红未成熟
		$tMywallet['newmint'] = empty($tCoinsinfo['newmint'])?0:$tCoinsinfo['newmint'];
		$tMywallet['blocks'] = empty($tCoinsinfo['blocks'])?0:$tCoinsinfo['blocks'];
		$tMywallet['numberoftransactions'] = empty($tCoinsinfo['numberoftransactions'])?0:$tCoinsinfo['numberoftransactions'];
		$tMywallet['newmint'] += $tMywallet['stake']; 

		if(!$tCoinsinfo = Cache_File::get('listaddressgroupings',20)){
			$tCoinsinfo = Tool_Fnc::rpc_client($tMywallet)->listaddressgroupings();
			if(!empty($tCoinsinfo['error']) &&!is_null($tCoinsinfo['error'])){Tool_Fnc::ajaxMsg( $this->lang->mywallet->acquiring_wallet_data_abnormal." list ".$tCoinsinfo['error'] , '0' , $tCoinsinfo['error'] );}
			Cache_File::set('listaddressgroupings' , $tCoinsinfo);
		}

		$tBalance = 0;
		foreach($tCoinsinfo as $tRows){
			foreach((array)$tRows as $tRow){
				if($tRow["symbol"] != $pWallet){break;}	
				$tBalance += $tRow["amount"];
			}
		}

		$tMywallet['balance'] = Tool_Fnc::etonumber($tBalance);

		if($this->l == 'en'){
			$tMywallet['name'] = ucfirst($tMywallet['en_name']); 		
			if($tMywallet['name'] == 'Ybcoin'){
				$tMywallet['name']  = 'YBC';	
			}
		}

		Tool_Fnc::ajaxMsg('',1,$tMywallet);
	}
}

