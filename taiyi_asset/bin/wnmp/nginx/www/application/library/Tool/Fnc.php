<?php
class Tool_Fnc{

	/**
	 * 真实IP
	 * @return string 用户IP
	 */
	static function realip(){
		foreach(array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR') as $v1){
			if(isset($_SERVER[$v1])){
				$tIP = ($tPos = strpos($_SERVER[$v1], ','))? substr($_SERVER[$v1], 0, $tPos): $_SERVER[$v1];
				break;
			}
			if($tIP = getenv($v1)){
				$tIP = ($tPos = strpos($tIP, ','))? substr($tIP, 0, $tPos): $tIP;
				break;
			}
		}
		return $tIP;
	}

	/**
	 * 提示信息
	 * @param string $pMsg
	 * @param bool $pUrl
	 */
	/**
	 * 提示信息
	 * @param string $pMsg 信息
	 * @param bool $pUrl 跳转到
	 */
	static function showMsg($pMsg, $pUrl = false){
		is_array($pMsg) && $pMsg = join('\n', $pMsg);
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		if('.' == $pUrl) $pUrl = $_SERVER['REDIRECT_URL'];
		echo '<script type="text/javascript">';
		if($pMsg) echo "alert('$pMsg');";
		if($pUrl) echo "self.location='{$pUrl}'";
		elseif(empty($_SERVER['HTTP_REFERER'])) echo 'window.history.back(-1);';
		else echo "self.location='{$_SERVER['HTTP_REFERER']}';";
		exit('</script>');
	}

	/**
	 * AJAX返回
	 *
	 * @param string $pMsg 提示信息
	 * @param int $pStatus 返回状态
	 * @param mixed $pData 要返回的数据
	 * @param string $pStatus ajax返回类型
	 */
	static function ajaxMsg($pMsg = '', $pStatus = 0, $pData = '', $pType = 'json'){
		# 信息
		$tResult = array('status' => $pStatus, 'msg' => $pMsg, 'data' => $pData);
		# 格式
		'json' == $pType && exit(json_encode($tResult));
		'xml' == $pType && exit(xml_encode($tResult));
		'eval' == $pType && exit($pData);
	}

	/**
	 * 信息返回
	 *
	 * @param string $pMsg 提示信息
	 * @param int $pStatus 返回状态
	 * @param mixed $pData 要返回的数据
	 * @param string $pStatus ajax返回类型
	 */
	static function Msg($pMsg = '', $pStatus = 0, $pData = '', $pType = 'json'){
		# 信息
		$tResult = array('status' => $pStatus, 'msg' => $pMsg, 'data' => $pData);
		# 格式
        $str ='';
		'json' == $pType && $str= json_encode($tResult);
		'xml' == $pType && $str=xml_encode($tResult);
		'eval' == $pType && $str=$pData;
        return $str;
	}

    /**
     * 接口请求
     *
     * @param string $pUrl 提交的地址
     * @param string $pData 发送的数据
     *
     * @return object
     */
	static function sendHttpPostData($url, $data_string='')
	{
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	
	    $return_content = curl_exec($ch);
	    $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    return json_decode($return_content,true);
	}


   
    /**
     * 二维数字根据key 排序
     */
    static function arraySort($arr, $keys, $type = 'desc') {
		if(!count($arr)){return $arr;}
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v) {
            $keysvalue[$k] = $v[$keys];
        }
        if ($type == 'asc') {
            asort($keysvalue);
        } else {
            arsort($keysvalue);
        }
        reset($keysvalue);
		$i=0;
        foreach ($keysvalue as $k => $v) {
            $new_array[$i] = $arr[$k];
			$i++;
        }
        return $new_array;
    }

	/**
     * 本周时间
     */
    static function current_week(){
        $tStart = strtotime(date('Y-m-d H:i:s', mktime(0, 0, 0,  date('m'), date('d') - date('N')+1, date('Y'))));
        $tEnd = strtotime(date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'),date('d') - date('N') + 7,date('Y'))));
        return array('start'=> $tStart,'end' => $tEnd);
    }

    static function before_week(){
        $tStart = strtotime(date('Y-m-d H:i:s', mktime(0, 0, 0,  date('m'), date('d') - date('N')-6, date('Y'))));
        $tEnd = strtotime(date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'),date('d') - date('N') ,date('Y'))));
        return array('start'=> $tStart,'end' => $tEnd);
    }

	/**
	 * 创建目录
	 */
	static function make_dir($path) {  
		$path = str_replace(array('/', '\\', '//', '\\\\'), DIRECTORY_SEPARATOR, $path);  
		$dirs = explode(DIRECTORY_SEPARATOR, $path);  
		$tmp = '';  
		foreach ($dirs as $dir) {  
        	$tmp .= $dir . DIRECTORY_SEPARATOR;  
			if (!file_exists($tmp) && !mkdir($tmp, 0777)) {  
            	return $tmp;  
			}  
		}  
		return true;  
	}

	/**
	 * 写入文件
	 */
	static function writefile($pFile,$pContent){
		$f = fopen($pFile , 'w+');			
		if(!fwrite($f,$pContent)){
			return false;	
		}
		fclose($f);
		return true;	
	}
	/**
	 * 读文件
	 */
	static function readfile($pFile){
		$f = fopen($pFile , 'r+');
		if(!$f)
			return false;
		$size = filesize($pFile);
		if(!$size)
			return false;
		$tStr = fread($f,$size);
		if(!$tStr){
			return false;	
		}
		fclose($f);
		return $tStr;	
	}

	/**
	 * 解压
	 */
	static function unzip($pFile,$pExtractdir){
		$zip = new ZipArchive;
		$res = $zip->open($pFile);
		if ($res === TRUE) {
			$zip->extractTo($pExtractdir);
			$zip->close();
			return true;
		}	 
		return false;
	 }

	 /**
	  * RPC 连接
	  */
	static function rpc_client($pData){
		$tRPC = new Source_jsonRPCClient('http://'.$pData['rpcuser'].':'.$pData['rpcpassword'].'@127.0.0.1:'.$pData['rpcport'].'/');	
		return $tRPC;
	}
	/**
	 * 连接sqlite数据库
	 */
	 static function sqlite_appdata(){
		$tAppdatadb = APPDATA . '/taiyi/common/webdb/';
		if(!is_dir($tAppdatadb)){
			self::make_dir($tAppdatadb);
		}
		$tAppdatadb .= 'taiyi.db';
		if(!is_file($tAppdatadb)){
			if(!copy(DB_FILE,$tAppdatadb)){
				$tAppdatadb = DB_FILE;	
			}
		}
		return $tSqlite = new Orm_Sqlite($tAppdatadb);
	 }
	 static function sqlite(){
		return $tSqlite = new Orm_Sqlite(DB_FILE);
	 }

	 /*
	* 连接cfossyncinfo数据库
	*/
	 static function sqlite_cfossyncinfo(){
		return $tSqlite = new Orm_Sqlite(CFOSDB_FILE);
	 }
	 /*static function sqliteLock(){
		$tSqlite = null;
		while(true){
			$tTime = time();
			$tSqlite = new Orm_Sqlite(DB_FILE);
			$tRow = $tSqlite->getRow('select IsBlocked from lock');
			if($tRow['IsBlocked'] == 0){
				sleep(1);
          		$tSqlite->query('update lock set IsBlocked=1,updated='.$tTime);
				$tSqlite->close();
				unset($tSqlite);
				break;
			}else{
				if(($tTime-$tRow['updated']) > 5){
					$tSqlite->close();
					unset($tSqlite);
					break;		
				}			
			}
			$tSqlite->close();
			unset($tSqlite);
			sleep(0.5);
		}
			 
	 }
	 static function sqliteCloseLock(){
		$tSqlite = new Orm_Sqlite(DB_FILE);
		sleep(1);
		$tSqlite->query('update lock set IsBlocked=0');
		$tSqlite->close();
	}
	*/

	 static function sqliteLock(){
		$tLockfile = USER_DIR . '/cache/.lock'; 
		while(true){
			if(!is_file($tLockfile)){
    			file_put_contents($tLockfile, '');
				break;
			}else{
				$tTime = time();			
				$tFiletime = filemtime($tLockfile);
				if(($tTime - $tFiletime) > 5){break;}
			}	
		}
	 }

	static function sqliteCloseLock(){
		$tLockfile = USER_DIR . '/cache/.lock'; 
		if(is_file($tLockfile)){
			unlink($tLockfile);
		}
	}
		
	 /**
	  * 删除目录
	  */
	 static function deldir($dirName){
		if ($handle = opendir("$dirName")) {  
			while(false !== ($item = readdir($handle))){  
				if($item != "." && $item != ".."){  
					if(is_dir("$dirName/$item")){  
						self::deldir("$dirName/$item");  
					}else{  
						unlink("$dirName/$item");
					}  
				}  
			}  
			closedir($handle);  
			return rmdir($dirName); 
		}   
		return false;
	 }
	 //获取文件列表
	 static function getFile($dir) {
		$fileArray=array();
		if (false != ($handle = opendir ( $dir ))) {
			while ( false !== ($file = readdir ( $handle )) ) {
            	if ($file != "." && $file != ".."&&strpos($file,".")) {
                	$fileArray[]=$file;
                }
            }
        	closedir ( $handle );
    	}
		return $fileArray;
	}
	static function dirsize($pDir){
		$tFilebat = SOFT_DIR . '/bin/TaiYiHelper.exe';
		$tFilebat = str_replace('\\','/',$tFilebat);
		$tFilesize = exec("\"".$tFilebat."\"  getdirsize " . ' ' . "\"".$pDir."\"");
		return floor($tFilesize/1024/1024);
	}
	static function array_column($pArray , $pKey){
		$tData = array();
		foreach($pArray as $tRow){
			$tData[] = $tRow[$pKey];	
		}
		return $tData;
	}

	/**
	 * 科学计数法 转正常
	 */
	static function etonumber($pNumber){
		$tNumber = sprintf('%.9f',$pNumber);	
		return $tNumber = rtrim(rtrim( $tNumber , '0') , '.');
	}
}
