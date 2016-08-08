<?php
# 全局
define("APPLICATION_PATH", realpath((phpversion() >= "5.3"? __DIR__: dirname(__FILE__)).'/../'));

$tHomepath = 'C:/ProgramData';  //exec('echo %APPDATA%');
define('SOFT_DIR' , realpath(dirname(__FILE__).'../../../../../../'));
define('WWW_DIR' ,  SOFT_DIR . '/bin/wnmp/nginx/www');
define('DB_FILE' , SOFT_DIR . '/user/webdb/taiyi.db');
define('CFOSDB_FILE' ,  SOFT_DIR . '/bin/wallets/cfos/cfosinfo/cfoswallet.db');      //added 2016/6/4  .the location of cfoswallet.db which provide info from cfos
define('APPDATA' , $tHomepath);
define('USER_DIR' , SOFT_DIR . '/user');
define('CFOS_EXE' ,  SOFT_DIR . '/bin/wallets/cfos/cfos.exe');      //added 2016/7/7
define('CFOS_DATADIR' ,  APPDATA . '/taiyi/abcoin');      //added 2016/7/7
define('HELPER_EXE' ,  SOFT_DIR . '/bin/TaiYiHelper.exe');      //added 2016/7/7
define('PHP_EXE' ,  SOFT_DIR . '/bin/wnmp/php5/php.exe');      //added 2016/7/7
define('CFOSPHP_FILE' ,  SOFT_DIR . '/bin/wallets/cfos/cfosinfo/updateinfo.php');

date_default_timezone_set("Asia/Shanghai");
# 域名前缀(cdn)
function host(){return '';}
#function host(){return 'http://s.btctrade.com';}
# 用户IP
foreach(array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR') as $v1){
	if(isset($_SERVER[$v1])){
		define('USER_IP', ($tPos = strpos($_SERVER[$v1], ','))? substr($_SERVER[$v1], 0, $tPos): $_SERVER[$v1]); break;
	}
	if($tIP = getenv($v1)){
		define('USER_IP', ($tPos = strpos($tIP, ','))? substr($tIP, 0, $tPos): $tIP); break;
	}
}
defined('USER_IP') || define('USER_IP', '1.2.3.4');
# 加载配置文件
error_reporting(E_ALL);

#$tLang = Yaf_Registry::get("config")->lang;			
#$tLang = empty($tLang)?'cn':$tLang;
define('LANG' , 'cn');

# 加载配置文件
$app = new Yaf_Application(APPLICATION_PATH . "/conf/application.ini", 'common');
$app->bootstrap()->run();
