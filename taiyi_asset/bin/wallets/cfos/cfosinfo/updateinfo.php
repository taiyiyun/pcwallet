<?php
if(count($argv)!=4){
	echo "updateinfo.php getinfo value(int) isremote(int)";
}
define('CFOSINFO_DIR' , realpath(dirname(__FILE__).'../'));
$db_name = CFOSINFO_DIR.'/cfoswallet.db';
$db_lock = CFOSINFO_DIR.'/db.lock';

//首先lock等待
$lockfile = fopen($db_lock,'r');
if(!$lockfile){
	return 'no lock';
}
while (!flock($lockfile,LOCK_EX)){
	usleep(200000);
}
//独占执行
$value = updateinfo($argv,$db_name);
while(!flock($lockfile,LOCK_UN)){ }
fclose($lockfile);
exit($value);

//function updateinfo
function updateinfo($argv, $db_name){
    //打开数据库文件
	$db = new sqlite3($db_name);
	if(!$db) {
		return 'no db'; 
	}  
	 
	//更新一条数据  
	if($argv[1]=="getinfo"){
		$val = $argv[2];
		$isremote = $argv[3];
		if (!$db->exec("UPDATE updateinfo set value=".$val.","."isremote=".$isremote."")) {
			$db->close();
			return 'update failed';
		}
	}
	return "\nupdate ".$val.", ".$isremote."\n";
}
 
 
?> 