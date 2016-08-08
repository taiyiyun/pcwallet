<?php
class TaiyiServiceController extends Ctrl_Base{
	private  $tDatataiyi = array(
			'rpcport' => 9490,
			'rpcuser' => 'ruhua',
			'rpcpassword' => 'ruhua'
		);
	public function wAction(){
			
		$pAct = empty($_GET['act'])?0:trim($_GET['act']);
		if(empty($pAct) || $pAct == 'show'){
			Tool_Fnc::rpc_client($this->tDatataiyi)->showwindow(true);
		}elseif($pAct == 'hide'){
			Tool_Fnc::rpc_client($this->tDatataiyi)->showwindow(false);
		}
		exit;
	
	}
    public function indexAction(){
		set_time_limit(0);
		/*$this->stop();
		sleep(6);
		$this->restart();
		*/

		Tool_Fnc::ajaxMsg('重启成功' , 1);	
    }
	private function stop(){
		$tRes = Tool_Fnc::rpc_client($this->tDatataiyi)->stop();
		#if($tRes != 'Ybshares server stopping'){

		#	Tool_Fnc::ajaxMsg('启动失败:'.$tRes , 0);	
		#}
	}
	public function stopAction(){
		$this->stop();	
		exit;
	}

	public function restartAction(){
		$this->restart();	
		exit;
	}
	private function restart(){
		pclose(popen(SOFT_DIR . '/bin/restarttaiyi.bat',"r"));
	}

	public function nginxstopAction(){
		pclose(popen(SOFT_DIR . '/bin/wnmp/nginx/stop_nginx.bat',"r"));
		exit;
	}

	public function walletlistreloadAction(){

		$tRes = Tool_Fnc::rpc_client($this->tDatataiyi)->walletlistreload();
		exit;
	}
	public function walletAction(){
		$tAct = empty($_GET['act'])?'':trim($_GET['act']);
		$tWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);
		if(empty($tAct) || empty($tWallet)){
			Tool_Fnc::ajaxMsg(0,'参数有问题');	
		}
		$tRes = Tool_Fnc::rpc_client($this->tDatataiyi)->wallet($tAct,$tWallet);
		if($tRes){
			if($tAct != 'status'){
				$this->sqlite()->query('update mywallet set unlock=0 where mark = \''.$tWallet.'\'');		
			}
			
			Tool_Fnc::ajaxMsg(1 , '',  $tRes);	
		}
		Tool_Fnc::ajaxMsg(0,'异常错误' );	
				
		exit;
		
	}

}
