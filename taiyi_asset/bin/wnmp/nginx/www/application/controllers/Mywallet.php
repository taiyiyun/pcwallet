<?php
class MywalletController extends Ctrl_Base{
    public function indexAction(){
		$tCfos = empty($_GET['cfos'])?1:0;
		$twallet_reindex = empty($_GET['reindex_wallet'])?'':trim($_GET['reindex_wallet']);
		$tSqlite = Tool_Fnc::sqlite();
		$tData = array();
		if(empty($twallet_reindex)){
			$tData = $tSqlite->getAll('select * from mywallet order by sort desc');
		}else{
			$tData = $tSqlite->getAll('select * from mywallet where mark=\''.$twallet_reindex.'\'');
			
			$tcfosdatadir = str_replace('\\','/',CFOS_DATADIR);
			$tprogressnotify = str_replace('\\','/',PHP_EXE.' '.CFOSPHP_FILE.' getinfo %d %d');
			$tStopcmd  = '"'.CFOS_EXE.' -datadir=\\"'.$tcfosdatadir.'\\" -checkblocks=1 stop"';
			$tStartcmd = '"'.CFOS_EXE.' -datadir=\\"'.$tcfosdatadir.'\\" -checkblocks=1 -reindex -progressnotify=\\"'.$tprogressnotify.'\\""';
			$treindexcmd = HELPER_EXE.' reindex '.$tStopcmd.' '.$tStartcmd;
			system($treindexcmd);
		}
		$tMywallet = $tCfosMywallet = array();
		foreach($tData as $tRow){
			if(!is_file(WWW_DIR.'/public'.$tRow['img'])){
				$tRow['img'] = 'http://clientapi.taiyilabs.com'.$tRow['img'];		
			}
			if(!is_file(WWW_DIR.'/public'.$tRow['small_img'])){
				$tRow['small_img'] = 'http://clientapi.taiyilabs.com'.$tRow['small_img'];		
			}
			if($tRow['cfos'] == 1 && $tRow['status'] == 3){$tCfosMywallet[] = $tRow;}else{$tMywallet[] = $tRow;}	
		}
		if(!empty($_GET['init'])){
			$tSqlite->query('update  db_version set version = \'v1.3\'');
			header('location: /');
		}

		$tSqlite->close();

		$this->assign('tMywallet' , $tMywallet);
		$this->assign('tCfosMywallet' , $tCfosMywallet);
		$this->assign('tCfos' , $tCfos);
		$this->assign('twallet_reindex' , $twallet_reindex);
		$this->display('mywallet/index');
	}

	public function walletinfoAction(){

		#Tool_Fnc::sqliteLock();
		$tSqlite = Tool_Fnc::sqlite();
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		if(empty($pWallet)){Tool_Fnc::ajaxMsg($this->lang->mywallet->wallet_index_cannot_be_empty , '0');}
		$tMywallet = $tSqlite->getRow('select name,en_name,mark,status,rpcuser,rpcpassword,rpcport from mywallet where mark=\''.$pWallet.'\'');
		if(empty($tMywallet) || !count($tMywallet)){Tool_Fnc::ajaxMsg($this->lang->mywallet->the_wallet_is_not_founded , '0' );}

		if($tMywalletdata = Cache_File::get('walletinfo:'.$pWallet,10)){
			Tool_Fnc::ajaxMsg('',1,$tMywalletdata);
		}
		$tCoinsinfo = Tool_Fnc::rpc_client($tMywallet)->getinfo();
		if(!empty($tCoinsinfo['error']) && !is_null($tCoinsinfo['error'])){
				Tool_Fnc::ajaxMsg( $this->lang->mywallet->acquiring_wallet_data_abnormal , '0' , $tCoinsinfo['error'] );exit;
		}
		$tMywallet['unconfirmed'] = empty($tCoinsinfo['unconfirmed'])?0:$tCoinsinfo['unconfirmed'];
		$tMywallet['stake'] = empty($tCoinsinfo['stake'])?0:$tCoinsinfo['stake'];#分红未成熟
		$tMywallet['balance'] = empty($tCoinsinfo['balance'])?0:$tCoinsinfo['balance'];
		$tMywallet['newmint'] = empty($tCoinsinfo['newmint'])?0:$tCoinsinfo['newmint'];
		$tMywallet['blocks'] = empty($tCoinsinfo['blocks'])?0:$tCoinsinfo['blocks'];
		$tMywallet['numberoftransactions'] = empty($tCoinsinfo['numberoftransactions'])?0:$tCoinsinfo['numberoftransactions'];
		$tMywallet['newmint'] += $tMywallet['stake']; 
		if($this->l == 'en'){
			$tMywallet['name'] = ucfirst($tMywallet['en_name']); 		
			if($tMywallet['name'] == 'Ybcoin'){
				$tMywallet['name']  = 'YBC';	
			}
		}

		$tPeerinfo = Tool_Fnc::rpc_client($tMywallet)->getpeerinfo();
		$tPeerheight = array();
		foreach($tPeerinfo as $tRow){
			if(!isset($tRow['height'])){$tRow['height'] = $tRow['startingheight']; }
			$tPeerheight[] = $tRow['height'];	
		}
		sort($tPeerheight);
		$tMywallet['height'] = $tPeerheight[floor(count($tPeerheight)/2)];
		Cache_File::set('walletinfo:'.$pWallet , $tMywallet);
		$tSqlite->close();
		#Tool_Fnc::sqliteCloseLock();
		Tool_Fnc::ajaxMsg('',1,$tMywallet);
	}
	public function downheaderAction(){

		ob_start();
		@set_time_limit(300);//设置该页面最久执行时间为300秒

		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletdirtmp = USER_DIR . '/wallet/'.$pWallet.'/tmp/init.log';
		$tWalleinitstr = Tool_Fnc::readfile($tWalletdirtmp);
		if(empty($tWalleinitstr)){Tool_Fnc::ajaxMsg('钱包初始文件为空 001' , 0);}	
		$tDatas = json_decode($tWalleinitstr,true);

		$url=$tDatas['data']['static']['download'];
		$file = fopen ($url, "rb");
		if ($file) {
			//获取文件大小
			$filesize = -1;
			$headers = get_headers($url, 1);
			if ((!array_key_exists("Content-Length", $headers))) $filesize=0;
			$filesize = $headers["Content-Length"];
    
			if ($filesize != -1) {
				#数据临时文件
				$tWalletdirtmp = USER_DIR . '/wallet/'.$pWallet.'/tmp';
				if(!is_dir($tWalletdirtmp)){
					Tool_Fnc::make_dir($tWalletdirtmp);	
				}
				Tool_Fnc::writefile(USER_DIR . '/wallet/'.$pWallet.'/tmp/walletsize.txt',$filesize);
			}
		}
		if ($file) {
			fclose($file);
		}
		exit;
	}
	private function download($pUrl,$pWallet){
		ob_start();
		@set_time_limit(300);//设置该页面最久执行时间为300秒
		$url=$pUrl;
		$newfname=USER_DIR . '/wallet/'.$pWallet.'/tmp/'.basename($pUrl);//本地存放位置，这样做在Win7下要设置相应权限
		$file = fopen ($url, "rb");
		if ($file) {
			$newf = fopen ($newfname, "wb");
			$downlen=0;
			if ($newf) {
				while(!feof($file)) {
					$data=fread($file, 1024 * 8 );//默认获取8K
					$downlen+=strlen($data);//累计已经下载的字节数
					fwrite($newf, $data, 1024 * 8 );
					#echo "<script>setDownloaded($downlen);</script>";//在前台显示已经下载文件大小
					Tool_Fnc::writefile(USER_DIR . '/wallet/'.$pWallet.'/tmp/walletdownloadsize.txt',$downlen);
					ob_flush();
					flush();
				}
			}
			if ($file) {
				fclose($file);
			}
			if ($newf) {
				fclose($newf);
			}
			return $downlen;
		}		
	}
	public function downwalletsizeAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		echo $tData = Tool_Fnc::readfile(USER_DIR . '/wallet/'.$pWallet.'/tmp/walletsize.txt');
		exit;
	}	
	public function downwalletnowsizeAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		echo Tool_Fnc::readfile(USER_DIR . '/wallet/'.$pWallet.'/tmp/walletdownloadsize.txt');
		exit;
	}	

	/**
	 * 得到钱包初始化文件
	 */
	public function downloadinitAction(){

		$tServerurl = Yaf_Registry::get("config")->serverurl;			
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);

		$tWalletinfo = $this->sqlite()->getRow($tSql = 'select * from mywallet where mark = \'' .$pWallet. '\'');

		$tRes = Tool_Fnc::sendHttpPostData($tServerurl . 'clientapi/downloadinit?wallet='.$pWallet.'&version='.$tWalletinfo['version']);	
		
		if(empty($tRes['status'])){
			Tool_Fnc::ajaxMsg($this->lang->mywallet->message1 . ' : 001' , 0 );	
		}

		$tWalletdir = USER_DIR . '/wallet';
		#if(is_dir($tWalletdir . '/' .$pWallet)){
			#Tool_Fnc::ajaxMsg('您的钱包已经安装过 002' , 0 );	
		#}
	
		#数据临时文件
		$tWalletdirtmp = $tWalletdir.'/'.$pWallet.'/tmp';
		if(!is_dir($tWalletdirtmp)){
			Tool_Fnc::make_dir($tWalletdirtmp);	
		}
		if(!Tool_Fnc::writefile($tWalletdirtmp.'/init.log',json_encode($tRes))){
			Tool_Fnc::ajaxMsg($this->lang->mywallet->initializing_data_file_input_error . '003' , 0 );	
		}
		
		$tHomepath = exec('echo %APPDATA%');
		$tWalletdatapath = $tHomepath . '/taiyi/' . $pWallet;
		$tConfstr = '';

		foreach($tRes['data']['conf'] as $tKey => $tVal){
			$tConfstr .= $tKey .'=' . $tVal. "\r\n";	
		}

		#用户数据目录 conf
		if(!is_dir($tWalletdatapath)){
			Tool_Fnc::make_dir($tWalletdatapath);	
		}
		if(!Tool_Fnc::writefile($tWalletdatapath.'/'.$pWallet.'.conf',$tConfstr)){
			
			Tool_Fnc::ajaxMsg($this->lang->mywallet->initializing_data_file_input_error.'004' , 0 );	
		}
			

		#太一 启动其他 币的xml
		$tRunxml = file_get_contents(USER_DIR . '/conf.xml');
		$tRunxml = new SimpleXMLElement($tRunxml);
		$twXML = 0;
		foreach ($tRunxml->service_list->children() as $child){
			if($child->name == $pWallet){
				#Tool_Fnc::ajaxMsg('太一钱包启动文件中该钱包的启动信息存在 005' , 0 );	
				$twXML = 1;
			}
		}
		if(empty($twXML)){
			$tRunxml_service = $tRunxml->service_list->addChild('service');
			$tRunxml_service->addChild('path' , '..\user\wallet\\'.$pWallet.'\\' );
			foreach($tRes['data']['xml'] as $tKey =>$tVal){
				$tRunxml_service->addChild($tKey , $tVal);
			}
			$tRunxmlstr = $tRunxml->asXML();
			if(!Tool_Fnc::writefile(USER_DIR . '/conf.xml',$tRunxmlstr)){
				Tool_Fnc::ajaxMsg($this->lang->mywallet->initializing_data_file_input_error.'006' , 0 );	
			}
		}

		$this->sqlite()->query($tSql = 'update mywallet set status = 1 where mark = \'' .$pWallet. '\'');
		Tool_Fnc::ajaxMsg($this->lang->system->succeed , 1);	
	}
	#下载中。。
	function downloadrunAction(){
		set_time_limit(0);
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
	
		$tWalletdirtmp = USER_DIR . '/wallet/'.$pWallet.'/tmp/init.log';
		$tWalleinitstr = Tool_Fnc::readfile($tWalletdirtmp);
		if(empty($tWalleinitstr)){Tool_Fnc::ajaxMsg($this->lang->mywallet->wallet_initializing_file_is_empty.' 001' , 0);}	
		$tDatas = json_decode($tWalleinitstr,true);

		if(empty($tDatas['data']['static']['download'])){Tool_Fnc::ajaxMsg($this->lang->mywallet->download_address_does_not_exist . ' 002' , 0);}

		#Tool_Fnc::writefile(USER_DIR . '/wallet/'.$pWallet.'/tmp/'.basename($tDatas['data']['static']['download']),file_get_contents($tDatas['data']['static']['download']));
		$tDownsize = $this->download($tDatas['data']['static']['download'],$pWallet);
		$tContsize = Tool_Fnc::readfile(USER_DIR . '/wallet/'.$pWallet.'/tmp/walletsize.txt');
		if($tDownsize == $tContsize){
			$this->sqlite()->query($tSql = 'update mywallet set status = 2 where mark = \'' .$pWallet. '\'');
			Tool_Fnc::ajaxMsg($this->lang->system->succeed , 1);		
		}else{
			$this->sqlite()->query($tSql = 'update mywallet set status = 0 where mark = \'' .$pWallet. '\'');
			Tool_Fnc::ajaxMsg($this->lang->mywallet->message2 , 0);		
		}

	}
	#安装中
	function installAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletinfo = $this->sqlite()->getRow($tSql = 'select * from mywallet where mark = \'' .$pWallet. '\'');

		$tWallettmpdir = USER_DIR.'/wallet/'.$pWallet.'/tmp/';
		$tWalletdir = USER_DIR.'/wallet/'.$pWallet.'/';
		if(!is_file($tWallettmpdir . $pWallet . '_'.$tWalletinfo['version'].'.zip')){
			$this->sqlite()->query($tSql = 'update mywallet set status = 0 where mark = \'' .$pWallet. '\'');
			Tool_Fnc::ajaxMsg($this->lang->mywallet->installation_files_do_not_exist . ' 001' , 0);
		}	
		$tDownsize = filesize($tWallettmpdir . $pWallet . '_'.$tWalletinfo['version'].'.zip');
		$tContsize = Tool_Fnc::readfile(USER_DIR . '/wallet/'.$pWallet.'/tmp/walletsize.txt');
		if($tDownsize != $tContsize){
			$this->sqlite()->query($tSql = 'update mywallet set status = 0 where mark = \'' .$pWallet. '\'');
			Tool_Fnc::ajaxMsg($this->lang->mywallet->message2 , 0);		
		}

		#if(is_dir($tWalletdir)){Tool_Fnc::ajaxMsg('钱包已经安装 002' , 0);}
		Tool_Fnc::unzip($tWallettmpdir . $pWallet. '_'.$tWalletinfo['version'] . '.zip' , $tWalletdir);

		$this->sqlite()->query($tSql = 'update mywallet set status = 3 where mark = \'' .$pWallet. '\'');
		Tool_Fnc::ajaxMsg($this->lang->system->succeed , 1);		
	}

	private function checklogo(){
		$tWallet = $this->sqlite()->getAll($tSql = 'select * from mywallet');
		$tImgdir = WWW_DIR . '/public';
		$tUrl = 'http://clientapi.taiyi.yuanbao.com/';
		foreach($tWallet as $tRow){
			if(!is_file($tImgdir . $tRow['img'])){
				$tImgcont = Tool_Fnc::readfile($tUrl . $tRow['img']); 
				Tool_Fnc::writefile($tImgdir . $tRow['img'] , $tImgcont);			
			}	
			if(!is_file($tImgdir . $tRow['small_img'])){
				$tImgcont = Tool_Fnc::readfile($tUrl . $tRow['small_img']); 
				Tool_Fnc::writefile($tImgdir . $tRow['small_img'] , $tImgcont);			
			}
		}
	}

	/**
	 * 判断钱包状态
	 */
	function walletstatusAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletinfo = $this->sqlite()->getRow($tSql = 'select * from mywallet where mark = \'' .$pWallet. '\'');
		
		$tPassphrase =Tool_Fnc::rpc_client($tWalletinfo)->walletpassphrase('zyr123',60);
		$tPasswallet = 0;
		if(!is_null($tPassphrase['error']) && ($tPassphrase['error']['code'] == '-15')){
			$tPasswallet = 1;//钱包没有密码
		}

		Tool_Fnc::ajaxMsg('',1,array('passwallet' => $tPasswallet,'unlock' => $tWalletinfo['unlock']));		
	}
	/**
	 * 得到钱包名称
	 */
	public function walletnameAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		$tWalletrow = array();
		if($pWallet == 'cfos'){
			$tWalletrow = array('name' => '太一资产');
			if($this->l == 'en'){$tWalletrow = array('name' => 'TaiYi Asset');}
			
		}else{
			$tWalletrow = $this->sqlite()->getRow('select name,en_name from mywallet where mark=\''.$pWallet.'\' limit 1');
			if($this->l == 'en'){$tWalletrow = array('name' => $tWalletrow['en_name']);}
		}
		Tool_Fnc::ajaxMsg('',1,$tWalletrow);
	}
}

