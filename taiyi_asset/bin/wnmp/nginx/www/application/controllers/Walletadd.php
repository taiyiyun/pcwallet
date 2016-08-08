<?php
class WalletaddController extends Ctrl_Base{
    public function indexAction(){
		$tCfos = empty($_GET['cfos'])?1:$_GET['cfos'];

		$this->assign('tCfos' , $tCfos);
		$this->display('walletadd/index');
    }

	private function getwalletall($pKeyword = '',$pCfos=1){
		
		$tWhere = '';
		if(!empty($pKeyword)){
			$tWhere = ' and (name like \'%'.$pKeyword.'%\' or en_name like \'%'.$pKeyword.'%\')';	
		}

		$wData = $this->sqlite()->getAll('select beta,img,name,en_name,mark from wallet where status = 0 and cfos = 1' . $tWhere . ' group by mark  order  by sort desc');		

		$tHtml = '';
		$tHtml .= '<div class="tagContent" id="tagContent1">';
		if(count($wData)){
			foreach($wData as $tRow){
				if($this->l == 'en'){
					$tRow['name'] = ucfirst($tRow['en_name']);
				}
				$tBeta = '';
				if($tRow['beta'] == 1){$tBeta = '<p class="beta"></p>';}
				$tHtml .= '<div class="coin left">
				<a href="/walletadd/introduce?wallet='.$tRow['mark'].'" title="'.$this->lang->walletadd->check_coin_introductions.'">
					<div class="coin_img">
						<p><img src="../..'.$tRow['img'].'" /></p>
						<h1 class="Font18">'.$tRow['name'].'</h1>
					</div>
				</a>
					<p><a href="/walletadd/introduce?wallet='.$tRow['mark'].'" class="plus_btn">'.$this->lang->walletadd->add.'</a></p>
					'.$tBeta.'
				</div>';
			}
		}else{
			$tHtml .= '<div class="no_con"><p>'.$this->lang->add_a_coin->no_new_asset.'</p></div>';			
		}
		$tHtml .= '</div>';
		return $tHtml;
	}

	public function searchlocalAction(){
		$pKeyword = empty($_GET['keyword'])?'':trim($_GET['keyword']);
		$pCfos = empty($_GET['cfos'])?1:trim($_GET['cfos']);

		$tHtml = self::getwalletall($pKeyword,$pCfos);
		Tool_Fnc::ajaxMsg($this->lang->walletadd->synchronized_successfully, 1,$tHtml);
	}

	public function addAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);			
		$pCfos = empty($_GET['cfos'])?'':trim($_GET['cfos']);			
		if($pCfos == 1 && !is_file(SOFT_DIR.'/cfos/wallets/cfos.exe')){
			Tool_Fnc::ajaxMsg('您的太一版本过低,请退出太一重试', 0);
		}

		if(empty($pWallet)){Tool_Fnc::ajaxMsg($this->lang->walletadd->index_error, 0);}
		$tGetwallet = $this->sqlite()->getRow($tSql = 'select * from wallet where mark = \''.$pWallet.'\' and status = 0');
		if(empty($tGetwallet)){Tool_Fnc::ajaxMsg($this->lang->walletadd->there_is_no_information_for_the_coin, 0);}
		
		$tMywallet = $this->getWallet($pWallet);
		if(!empty($tMywallet) && count($tMywallet)){Tool_Fnc::ajaxMsg($this->lang->walletadd->the_coin_purse_has_been_added, 0);}

		$tTime = time();
		$tStatus = 0;
		if($tGetwallet['cfos'] == 1){$tStatus = 3;}
		$tSort = 0;

		$tData = array(
			'name' => $tGetwallet['name'],
			'en_name' => $tGetwallet['en_name'],
			'mark' => $tGetwallet['mark'],
			'introduce' => addslashes(htmlspecialchars($tGetwallet['introduce'],ENT_QUOTES)),
			'en_introduce' => addslashes(htmlspecialchars($tGetwallet['en_introduce'],ENT_QUOTES)),
			'rpcuser' => $tGetwallet['rpcuser'],
			'rpcpassword' => $tGetwallet['rpcpassword'],
			'rpcport' => $tGetwallet['rpcport'],
			'created' => $tTime,
			'status' => $tStatus,
			'version' => $tGetwallet['version'],
			'img' => $tGetwallet['img'],
			'small_img' => $tGetwallet['small_img'],
			'filesize' => $tGetwallet['filesize'],
			'pos' => $tGetwallet['pos'],
			'explorer' => $tGetwallet['explorer'],
			'cfos' => $tGetwallet['cfos'],
			'beta' => $tGetwallet['beta'],
			'sort' => $tGetwallet['sort'],
		);
		$this->sqlite()->insert('mywallet',$tData);
		$this->sqlite()->query('update wallet set status = 1,updated='.$tTime.' where mark = \''.$pWallet.'\'');
		Tool_Fnc::ajaxMsg($this->lang->wallet->added_successfully, 1);
		exit;
	}
	public function introduceAction(){
		$pWallet = empty($_GET['wallet'])?'':trim($_GET['wallet']);					
		if(empty($pWallet)){Tool_Fnc::ajaxMsg($this->lang->walletadd->index_error, 0);}
		$tGetwallet = $this->sqlite()->getRow($tSql = 'select * from wallet where mark = \''.$pWallet.'\'');
		if(empty($tGetwallet)){Tool_Fnc::ajaxMsg($this->lang->walletadd->there_is_no_information_for_the_coin, 0);}

		$tWalletinfo = $this->sqlite()->getRow($tSql = 'select * from mywallet where mark = \''.$pWallet.'\'');
		if(!is_file(WWW_DIR.'/public'.$tGetwallet['img'])){
			$tGetwallet['img'] = 'http://clientapi.taiyilabs.com'.$tGetwallet['img'];		
		}
		if(!is_file(WWW_DIR.'/public'.$tGetwallet['small_img'])){
			$tGetwallet['small_img'] = 'http://clientapi.taiyilabs.com'.$tGetwallet['small_img'];		
		}

		$this->assign('tGetwallet' ,  $tGetwallet);
		$this->assign('tWalletinfo' ,  $tWalletinfo);
		$this->display('walletadd/introduce');
	}
}
