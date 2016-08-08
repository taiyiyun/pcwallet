<?php
/**
 * 基础类
 */
abstract class Ctrl_Base extends Yaf_Controller_Abstract{
	/**
	 * 开启 SESSION : 1
	 * 必须登录 : 2
	 * 必须管理员 : 4
	 */
	protected $_auth = 0;

	/**
	 * 当前登录用户
	 * @var array
	 */
	public $mCurUser = array();
	public $l;
	public $lang;

	/**
	 * 构造函数
	 */
	public function init(){
		$tSqlite = $this->sqlite();
		$tLCount = $tSqlite->getRow("SELECT COUNT(0) c FROM sqlite_master where type='table' and name='lang'");
		$tLang = array();
		if(!empty($tLCount['c'])){$tLang = $tSqlite->getRow($tSql = 'select * from lang');}
		
		$tLang = empty($tLang['lang'])?'cn':$tLang['lang'];
		include realpath(dirname(__FILE__).'../../../lang/'.$tLang.'/common.php');
		$lang = json_encode($lang);
		$lang = json_decode($lang);
		$this->assign('lang' , $lang);
		$this->assign('l' , $tLang);
		$this->l = $tLang;
		$this->lang = $lang;
		//(1 & $this->_auth) && $this->_session();
		//(1 < $this->_auth) && $this->_role();
		$tSqlite->close();
	}


	/**
	 * 注册变量到模板
	 * @param str|array $pKey
	 * @param mixed $pVal
	 */
	protected function assign($pKey, $pVal = ''){
		if(is_array($pKey)){
			$this->_view->assign($pKey);
			return $pKey;
		}
		$this->_view->assign($pKey, $pVal);
		return $pVal;
	}

	/**
	 * 注册变量到布局
	 * @param str $k
	 * @param mixed $v
	 */
	protected function layout($k, $v){
		static $layout;
		$layout || $layout = Yaf_Registry::get('layout');
		@$layout->$k = $v;
		$this->assign($k, $v);
	}

	protected function display($phtml){
		$tLang = Yaf_Registry::get("config")->lang;			
		$tLang = empty($tLang)?'cn':$tLang;
		$this->_view->display($tLang . '/'.$phtml.'.phtml');exit;		
	}

	protected function getwallet($pWallet){
		$tSqlite = $this->sqlite();
		#验证该钱包是否存在
		$twRow = $tSqlite->getRow($tSql = 'select * from mywallet where mark = \'' .$pWallet.'\'');
		$tSqlite->close();
		if(empty($twRow)){return false;}
		return $twRow;
	}
	protected function sqlite($pType=''){
		if($pType =='appdata'){
			return 	$tSqlite = Tool_Fnc::sqlite_appdata();
		}
		return 	$tSqlite = Tool_Fnc::sqlite();
	}
	
	/**
	 * 得到appdata 目录信息
	 */
	protected function appdata_dir($pWallet){
		$tSqlite = $this->sqlite();
		#验证该钱包是否存在
		$twRow = $tSqlite->getRow($tSql = 'select * from mywallet where mark = \'' .$pWallet.'\'');
		if(empty($twRow['appdata_dir'])){
			$twRow['appdata_dir'] = '%APPDATA%/taiyi';
		}
		$tSqlite->close();
		return $twRow['appdata_dir'];
	}
}
