<?php
class MessageController extends Ctrl_Base{
    public function indexAction(){
		$tSqlite = Tool_Fnc::sqlite();
		$tSqlite->query('update message set status = 1 where status  = 0');
		$tLimit = 10;                                  
        $tContArr = $this->sqlite()->getRow('select count(0) c from message where language = \''.$this->l.'\'');                
        $tCnt = empty($tContArr['c'])?0:$tContArr['c'];                                               
        $tPage = new Tool_Page($tCnt, $tLimit);                                                        
		$tDatas = array();
		if(!empty($tCnt)){
        	$tDatas =$tSqlite->getAll('select * from message where language = \''.$this->l.'\' order by created desc limit ' . $tPage->limit());        
		}
		$tSqlite->close();
        $this->assign('tDatas' , $tDatas);                                                          
        $this->assign('pageinfo', $tPage->show());
        $this->assign('tHeadact', 'message');
		$this->display('Message/index');
    }

	public function checknewmessageAction(){
		$tSqlite = Tool_Fnc::sqlite();
		$tMywallet = $tSqlite->getAll('select name,en_name,mark,status,rpcuser,rpcpassword,rpcport from mywallet where status=3 and cfos<>1');

		$tMList = new ListtransactionsModel;
		foreach($tMywallet as $tRow){

			$tCount = $tSqlite->getRow('select count(0) c from message where wallet = \'' .$tRow['mark']. '\'');	
			$tC = 20;
			if(empty($tCount['c'])){
				$tC = 100000;	
			}
			$tTransactions = Tool_Fnc::rpc_client($tRow)->listtransactions('*',$tC,0);
			if(!count($tTransactions)) continue;
			$tTransactions = $tMList->listtransactions_exclude($tTransactions);
			foreach($tTransactions as $tTRow){
				if($tTRow['category'] != 'receive'){continue;}
				$tMRow = $tSqlite->getRow('select * from message where txid = \'' .$tTRow['txid']. '\' and wallet = \'' .$tRow['mark']. '\'');	
				if(count($tMRow) && is_array($tMRow)) continue;

				$tData = array(
					'content' => sprintf('<a class="modify" href="/wallet/listtransactions?wallet='.$tRow['mark'].'">您有一笔%s到账，数量为:&nbsp;',$tRow['name']) . Tool_Fnc::etonumber($tTRow['amount'])  . '&nbsp;'. $tRow['en_name'].'</a>',		
					'created' => $tTRow['time'],
					'language' => 'cn',
					'status' => 0,
					'txid' => $tTRow['txid'],
					'wallet' => $tRow['mark'],
				);	
				$this->sqlite()->insert('message' , $tData);
				$tData = array(
					'content' => sprintf('<a class="modify" href="/wallet/listtransactions?wallet='.$tRow['mark'].'">You have received a new %s transfer, amount:&nbsp;',$tRow['en_name']) .  Tool_Fnc::etonumber($tTRow['amount'])  . '&nbsp;'. $tRow['en_name']. '</a>',		
					'created' => $tTRow['time'],
					'language' => 'en',
					'status' => 0,
					'txid' => $tTRow['txid'],
					'wallet' => $tRow['mark'],
				);	
				$this->sqlite()->insert('message' , $tData);

			}
		}

		$tCount = $tSqlite->getRow('select count(0) c from message where status = 0 and language = \''.$this->l.'\'');
		$tCount = empty($tCount['c'])?0:$tCount['c'];
		$tSqlite->close();
		Tool_Fnc::ajaxMsg( '', 1 , array('messagecount' => $tCount) );
	}

	public function cfos_checknewmessageAction(){
    	$tSqlite = Tool_Fnc::sqlite();
		$tMywallet = $tSqlite->getAll('select name,en_name,mark,status,rpcuser,rpcpassword,rpcport from mywallet where status=3 and cfos=1 limit 1');

		$tMList = new ListtransactionsModel;
		foreach($tMywallet as $tRow){
		
			//cfos 区块
			if(!$tPeerinfo = Cache_File::get('getpeerinfo',10)){
				$tPeerinfo = Tool_Fnc::rpc_client($tRow)->getpeerinfo();
				Cache_File::set('getpeerinfo' , $tPeerinfo);
			}

			$tPeerheight = array();
			foreach($tPeerinfo as $tR){
				if(isset($tR['height'])){
					$tPeerheight[] = $tR['height'];	
				}else{
					if(isset($tR['startingheight'])){
						$tPeerheight[] = $tR['startingheight']; 
					}
				}
			}
			sort($tPeerheight);
			$tPeerheight = empty($tPeerheight)?500:$tPeerheight[floor(count($tPeerheight)/2)];

			if(!$tCoinsinfo = Cache_File::get('getinfo',10)){
				$tCoinsinfo = Tool_Fnc::rpc_client($tRow)->getinfo();
				if( !empty($tCoinsinfo['errors'])){continue;}
				Cache_File::set('getinfo' , $tCoinsinfo);
			}
			$tNum = $tPeerheight - $tCoinsinfo['blocks'];
			if(empty($tCoinsinfo['blocks']) || empty($tPeerheight) || $tPeerheight == null || $tNum > 0){
				Tool_Fnc::ajaxMsg('cfos' , 0);
			}

			$tTransactions = Tool_Fnc::rpc_client($tRow)->listsinceblock();
			if(!array_key_exists ( 'transactions' , $tTransactions )){continue;}
			$tTransactions = $tTransactions['transactions'];

			if(!count($tTransactions)) continue;
			$tTransactions = $tMList->listtransactions_exclude($tTransactions);
			foreach($tTransactions as $tTRow){
				if($tTRow['category'] != 'receive'){continue;}
				$tMRow = $tSqlite->getRow('select * from message where txid = \'' .$tTRow['txid']. '\' and wallet = \'' .$tTRow['symbol']. '\'');	
				if(count($tMRow) && is_array($tMRow)) continue;

				$tGet = $tSqlite->getRow('select name,en_name from mywallet where mark = \''.$tTRow['symbol'].'\'');
				$tData = array(
					'content' => sprintf('<a class="modify" href="/cfos_wallet/listtransactions?wallet='.$tTRow['symbol'].'">您有一笔%s到账，数量为:&nbsp;',$tGet['name']) .  Tool_Fnc::etonumber($tTRow['amount']) . '&nbsp;' . $tTRow['symbol'] . '</a>',		
					'created' => $tTRow['time'],
					'language' => 'cn',
					'status' => 0,
					'txid' => $tTRow['txid'],
					'wallet' => $tTRow['symbol'],
				);	
				$tSqlite->insert('message' , $tData);
				$tData = array(
					'content' => sprintf('<a class="modify" href="/cfos_wallet/listtransactions?wallet='.$tTRow['symbol'].'">You have received a new %s transfer, amount:&nbsp;',$tTRow['symbol']) .  Tool_Fnc::etonumber($tTRow['amount'])  . '&nbsp;'. $tTRow['symbol']. '</a>',		
					'created' => $tTRow['time'],
					'language' => 'en',
					'status' => 0,
					'txid' => $tTRow['txid'],
					'wallet' => $tTRow['symbol'],
				);	
				$tSqlite->insert('message' , $tData);

			}
		}

		$tCount = $tSqlite->getRow('select count(0) c from message where status = 0 and language = \''.$this->l.'\'');
		$tCount = empty($tCount['c'])?0:$tCount['c'];
		$tSqlite->close();
		Tool_Fnc::ajaxMsg( '', 1 , array('messagecount' => $tCount) );
	}
}
