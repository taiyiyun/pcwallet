<?php

class Orm_Sqlite
{	
	private $db = null;
	private $res = null;
		
	public function __construct($pHost=DB_FILE){
		if($this->db == NULL){
			$pHost = iconv('gb2312','utf-8',$pHost);
			$this->db = new SQLite3($pHost);
		}

		if(!$this->db){
			echo 'sqlite error';exit;	
		}
				
	}

	public function __destruct(){
		if(!empty($this->db)){
			$this->db->close();		
		}			
	}
	public function close(){
		if($this->db){
			$this->db->close();		
			unset($this->db);
		}			
		if($this->res){
			unset($this->res);
		}			
	
	}
	public function busytimeout($tMsecs=3){
		$this->db->buyTimeout($tMsecs);	
	}

	public function query($tSql){
		return $this->res = $this->db->query($tSql);
	}

	public function getRow($tSql){
		$tRes = self::query($tSql);
		if(!($tRes instanceof Sqlite3Result)){
			return array();
		}else{
			return $tRes->fetchArray(SQLITE3_ASSOC);
		}
	}

	public function getAll($tSql){
		$tRes = self::query($tSql);

        $tDatas = array(); 
        $i = 0; 
		while($tRow = $tRes->fetchArray(SQLITE3_ASSOC)){ 
			$tDatas[$i] = $tRow;		
			$i++; 
		} 

		return $tDatas;
	}
	 function insert($pTable,$pData){
        if(is_array($pData)){                                                                 
            $tField = '`'.join('`,`', array_keys($pData)).'`';                                      
            $tVal = join("','", $pData);                                                            
			$tRes = self::query($tSql = "INSERT INTO ".$pTable." (".$tField.") VALUES ('$tVal')");
            return $tRes; 
		}                                                                                           
        return 0;    			
	}
}

?>
