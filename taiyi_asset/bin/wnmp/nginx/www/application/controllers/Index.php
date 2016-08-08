<?php
class IndexController extends Ctrl_Base{
    public function indexAction(){
		//$this->taiyiinit();初始化的工作交给启动程序 。
		$this->display('Index/index');
    }
	public function initAction(){
		$this->taiyiinit();
		exit;
	}
	private function taiyiinit(){
		$tSqlite = Tool_Fnc::sqlite();
		$tSqlite_appdata = Tool_Fnc::sqlite_appdata();

		$tVersion = $tSqlite->getRow('select * from db_version limit 1');
		$tDbfile = $this->getupgradedb();
		if(empty($tVersion['version'])){
			$tSqlite()->query('update from db_version set version = \'' . max($tDbfile) . '\'');

		}else{
			foreach($tDbfile as $tVal){

				if(strnatcasecmp($tVal,$tVersion['version']) > 0){
					$tRes = Tool_Fnc::readfile(WWW_DIR . '/upgrade/data/db/'.$tVal.'.sql');
					$arr = explode("||",$tRes);
					foreach($arr as $v){

						Tool_Fnc::sqliteLock();
						$v = trim($v);
						if(preg_match('/--appdata--/i',$v)){
							$v = str_replace('--appdata--','',$v);
							sleep(0.2);
							$tSqlite_appdata->query($v);
						}else{
							sleep(0.2);
							$tSqlite->query($v);
						}

						Tool_Fnc::sqliteCloseLock();
					}
					$tSqlite->query('update  db_version set version = \'' . $tVal . '\'');

				}
			}
		}

		//初始化 cfos
		$tHomepath = exec('echo %APPDATA%');
		$tWalletdatapath = $tHomepath . '/taiyi/abcoin';
		if(!is_dir($tWalletdatapath)){
			Tool_Fnc::make_dir($tWalletdatapath);	
		}
		if(!is_file($tWalletdatapath.'/abcoin.conf')){
				
			$tStr = "rpcuser=abcoinrpc\r\nrpcpassword=5em948H6pFwNoL6P8u2uggEfTMsZCUL6xwSthPeMkPxc";
			Tool_Fnc::writefile($tWalletdatapath.'/abcoin.conf',$tStr);
		}	

		$tRunxml = file_get_contents(USER_DIR . '/conf.xml');
		$tRunxml = new SimpleXMLElement($tRunxml);
		$twXML = 0;
		foreach ($tRunxml->service_list->children() as $child){
			if($child->name == 'cfos'){
				$twXML = 1;
			}
		}
		$tSqlite->close();
		$tSqlite_appdata->close();
		if(empty($twXML)){
			$tRunxml_service = $tRunxml->service_list->addChild('service');
			$tRunxml_service->addChild('path' , '..\bin\wallets\cfos\\' );
			$tRunxml_service->addChild('name' , 'cfos' );
			$tRunxml_service->addChild('executable_file' , 'cfos.exe' );
			//$tRunxml_service->addChild('show' , 'true' );
			$tRunxml_service->addChild('arg' , '-datadir="%appdata%\taiyi\abcoin" -checkblocks=1' );
			$tRunxmlstr = $tRunxml->asXML();
			if(Tool_Fnc::writefile(USER_DIR . '/conf.xml',$tRunxmlstr)){
				file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/taiyiservice/walletlistreload');
				file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/taiyiservice/wallet?act=load&wallet=cfos');
			}
		}		
	}
	private function getupgradedb(){
			
		$tDbfile = Tool_Fnc::getFile(WWW_DIR . '/upgrade/data/db');
		if(!count($tDbfile)){return ;}
		$tArr = array();
		foreach($tDbfile as $tVal){
			$tArr[] = trim($tVal,'.sql');	
		}
		return $tArr;
	}

	public function logoutAction(){
		
		Cache_File::set('listaddressgroupings' , '');
		Cache_File::set('getinfo' , '');
		Cache_File::set('getpeerinfo' , '');
		$this->display('Index/logout');				
	}
	public function taiyiAction(){
		echo '1';exit;			
	}
}
