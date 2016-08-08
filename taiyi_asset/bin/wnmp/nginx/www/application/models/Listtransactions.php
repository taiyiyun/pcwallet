<?php
class ListtransactionsModel{
	public function listtransactions_exclude($pData){
		$tDatanew = array();
		if(!is_array($pData) || empty($pData)){return '';}
		foreach($pData as  $tRow){
			if(empty($tRow['txid'])){continue;}
			$tDatanew[$tRow['txid']][] = $tRow;	
		}

		$tDatanewtmp = array();
		foreach($tDatanew as $tKey =>$tRow){
			if(count($tRow) == 3){
				$tSend = 0;#发出记录
				$tArrval = array();#存储值
				foreach($tRow as $tK => $tV){
					if($tV['category'] == 'send'){
						$tSend += 1;	
					}	
					#$tArrval[$tK] = sprintf('%7f',$tV['amount']);
					$tArrval[$tK] = $tV['address'];
				}		
				$tArr = array();#存储记录
				if($tSend == 2){#认为 是转出记录
					$tD = 0;#得到正确的记录
					if($tArrval[0] == $tArrval[1]){
						$tD = $tArrval[2];	
					}elseif($tArrval[1] == $tArrval[2]){
						$tD = $tArrval[0];	
					}elseif($tArrval[0] == $tArrval[2]){
						$tD = $tArrval[1];	
					}

					//过滤找零记录
					foreach($tRow as $tV){
						if($tV['address'] != $tD){
							continue;	
						}	
						$tArr = $tV;
					}
				}

				if(count($tArr) > 0){
					$tDatanewtmp[$tKey][] = $tArr;
				}else{
					$tDatanewtmp[$tKey] = $tRow;
				}

			}else{
				$tDatanewtmp[$tKey] = $tRow;
			}	

		}
		#组合成正常的二维数组
		$tArrnew = array();
		foreach($tDatanewtmp as $tRow){
			foreach($tRow as $tV){
				$tV['amount'] = Tool_Fnc::etonumber($tV['amount']); 
				$tArrnew[] = $tV;	
			}	
		}
		return $tArrnew;
	}
	
}
