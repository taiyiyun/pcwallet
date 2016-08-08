<?php
class WalleteditController extends Ctrl_Base{
    public function indexAction(){
		$tCfos = empty($_GET['cfos'])?1:$_GET['cfos'];
		$wData = $this->sqlite()->getAll('select name,en_name,mark,rpcuser,rpcpassword,rpcport,img,small_img,version,cfos,pos,beta from mywallet where status = 3 order by sort desc');		
		$tHomepath = exec('echo %APPDATA%');
		foreach($wData as $tKey => $tRow){
			if($tRow['cfos'] == 1){$tRow['mark'] ='abcoin';}
			$tWalletdir = $tHomepath . '/taiyi/' . $tRow['mark'];
			$tDirsize = 0;
			if(!$tDirsize = Cache_File::get('dirsize:'.$tRow['mark'],10)){
				$tDirsize = Tool_Fnc::dirsize($tWalletdir);
				Cache_File::set('dirsize:'.$tRow['mark'] , $tDirsize);
			}
			$wData[$tKey]['size'] = $tDirsize;
		}
		$tWallet = array();
		$tCfosWallet = array();
		foreach($wData as $tRow){
			if(!is_file(WWW_DIR.'/public'.$tRow['img'])){
				$tRow['img'] = 'http://clientapi.taiyi.yuanbao.com/'.$tRow['img'];		
			}
			if(!is_file(WWW_DIR.'/public'.$tRow['small_img'])){
				$tRow['small_img'] = 'http://clientapi.taiyi.yuanbao.com/'.$tRow['small_img'];		
			}

			if(empty($tRow['cfos'])){
				$tWallet[] = $tRow;					
			}else{
				$tCfosWallet[] = $tRow;					
			}	
		}

		$this->assign('wData' , $wData);
		$this->assign('tWallet' , $tWallet);
		$this->assign('tCfosWallet' , $tCfosWallet);
		$this->assign('tCfos' , $tCfos);
		$this->display('walletedit/index');
    }

	public function updateAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);				
		if(empty($pWallet)){Tool_Fnc::ajaxMsg($this->lang->walletedit->message1,0);}

		$tMywalletinfo = $this->sqlite()->getRow('select * from mywallet where mark=\''.$pWallet.'\'');		
		$tWalletinfo = $this->sqlite()->getRow('select * from wallet where mark=\''.$pWallet.'\'');		

		if($tMywalletinfo['version'] == $tWalletinfo['version']){#版本比较
			Tool_Fnc::ajaxMsg($this->lang->walletedit->there_is_no_newer_edition,0);
		}

		$this->del($pWallet);

		$this->sqlite()->query('delete from mywallet where mark = \''.$pWallet.'\'');
		$tTime = time();	
		$tData = array(
			'name' => $tWalletinfo['name'],
			'en_name' => $tWalletinfo['en_name'],
			'mark' => $tWalletinfo['mark'],
			'introduce' => $tWalletinfo['introduce'],
			'en_introduce' => $tWalletinfo['en_introduce'],
			'rpcuser' => $tWalletinfo['rpcuser'],
			'rpcpassword' => $tWalletinfo['rpcpassword'],
			'rpcport' => $tWalletinfo['rpcport'],
			'created' => $tTime,
			'status' => 0,
			'version' => $tWalletinfo['version'],
			'img' => $tWalletinfo['img'],
			'small_img' => $tWalletinfo['small_img'],
			'filesize' => $tWalletinfo['filesize'],
			'pos' => $tWalletinfo['pos'],
		);
		$this->sqlite()->insert('mywallet',$tData);
		Tool_Fnc::ajaxMsg($this->lang->walletedit->upgrade_started,1);
	}

    public function deleteAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);				
		if(empty($pWallet)){Tool_Fnc::ajaxMsg($this->lang->walletedit->message2,0);}

		
		$this->del($pWallet);
		Tool_Fnc::ajaxMsg($this->lang->walletedit->deleted_successfully,1);
		exit;
	}

    public function cfos_deleteAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);				
		if(empty($pWallet)){Tool_Fnc::ajaxMsg($this->lang->walletedit->message2,0);}

		#删除库里数据
		$tSql = 'delete from mywallet where mark = \''.$pWallet.'\'';
		$this->sqlite()->query($tSql);
		$tSql = 'update wallet set status=0 where mark = \''.$pWallet.'\'';
		$this->sqlite()->query($tSql);

		Tool_Fnc::ajaxMsg($this->lang->walletedit->deleted_successfully,1);
		exit;
	}

	private function del($pWallet){
		$tWalletdir = str_replace('\\' , '/' ,USER_DIR . '/wallet/'.$pWallet);
		if(!is_dir($tWalletdir)){Tool_Fnc::ajaxMsg($this->lang->walletedit->message3,0);}
		#删除库里数据
		$tSql = 'delete from mywallet where mark = \''.$pWallet.'\'';
		$this->sqlite()->query($tSql);
		$tSql = 'delete from wallet where mark = \''.$pWallet.'\'';
		$this->sqlite()->query($tSql);

		#删除XML配置
		$tRunxml = file_get_contents(USER_DIR . '/conf.xml');
		$tRunxml = new SimpleXMLElement($tRunxml);
		$twXML = 0;

		$i = 0;
		foreach ($tRunxml->service_list->children() as  $child){
			if($child->name == $pWallet){
				unset($tRunxml->service_list->children()->$i);
			}
			$i++;
		}
		Tool_Fnc::writefile(USER_DIR . '/conf.xml',$tRunxml->saveXML());
		Tool_Fnc::deldir($tWalletdir);
		
	}
}
