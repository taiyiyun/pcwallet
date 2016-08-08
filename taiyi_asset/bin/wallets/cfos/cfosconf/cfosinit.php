<?php
echo 'init for cfos start..';
//为启动cfos做一些初始化工作。
//cfos对外提供服务，需要指定rpc端口。这里生成abcoin.conf。这一步在cfos启动之前需要完成。
//2016/6/15
$tHomepath = 'C:/ProgramData';  //exec('echo %APPDATA%');
$tWalletdatapath = $tHomepath . '/taiyi/abcoin';
if(!is_dir($tWalletdatapath)){
	make_dir($tWalletdatapath);	
}
if(!is_file($tWalletdatapath.'/abcoin.conf')){
	$tStr = "rpcuser=abcoinrpc\r\nrpcpassword=5em948H6pFwNoL6P8u2uggEfTMsZCUL6xwSthPeMkPxc";
	writefile($tWalletdatapath.'/abcoin.conf',$tStr);
}
//cfos生成debug.log文件很大，删除
$tdebugfile = $tWalletdatapath.'/debug.log';
if(is_file($tdebugfile)){
	unlink($tdebugfile);
}
//cfos启动初始，会跟外网进行同步，同步的进度信息会写入文件， 这里指定这个文件。
//钱包需要cfos信息可以从这读取。这步需要启动之前完成。
//2016/6/15
$tcfoswalletdb = '../bin/wallets/cfos/cfosinfo/cfoswallet.db';
$tsourcedb = '../bin/wallets/cfos/cfosconf/cfoswallet.db';
if(is_file($tcfoswalletdb)){
	unlink($tcfoswalletdb);	
}
while(!copy($tsourcedb, $tcfoswalletdb)){}
//cfos写入信息可能并发， 需要文件lock
//2016/6/15
$tdblock = '../bin/wallets/cfos/cfosinfo/db.lock';
$tsclock = '../bin/wallets/cfos/cfosconf/db.lock';
if(is_file($tdblock)){
	unlink($tdblock);
}
while(!copy($tsclock, $tdblock)){}
/**
 * 创建目录
 */
function make_dir($path) {  
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
function writefile($pFile,$pContent){
	$f = fopen($pFile , 'w+');			
	if(!fwrite($f,$pContent)){
		return false;	
	}
	fclose($f);
	return true;	
}
?> 