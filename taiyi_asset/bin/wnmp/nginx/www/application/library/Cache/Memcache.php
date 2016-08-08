<?php
/**
 * MemCache 缓存
 */
class Cache_Memcache{
	private static $mem;

  /**
   * 构造函数
   */
  public function __destruct(){
		self::$mem->close();
	}

  /**
   * 单例接口
   * @param string $pConfig 服务器配置
   * @return Memcache
   */
  static function &instance($pConfig = 'default'){
		if (!isset(self::$mem[$pConfig])){
			//配置
			$tMC = Yaf_Registry::get("config")->mc->$pConfig->toArray();
			//连接
			self::$mem[$pConfig] = new Memcache();
			self::$mem[$pConfig]->pconnect($tMC['host'], $tMC['port']) or self::$mem[$pConfig] = null;
		}
		return self::$mem[$pConfig];
	}

  /**
   * 取缓存
   * @param $pKey
   * @return array|string
   */
  static function get($pKey, $pField = ''){
    if(!$pField) return Cache_Memcache::instance()->get($pKey);
    if($tData = Cache_Memcache::instance()->get($pKey)){
      if(isset($tData[$pField])) return $tData[$pField];
    }
    return false;
  }

  /**
   * 取缓存
   * @param $pKey
   * @param $pVal
   * @param int $pExp
   * @return bool
   */
  static function set($pKey, $pVal, $pExp = 3600){
    return Cache_Memcache::instance()->set($pKey, $pVal, $pExp);
  }
}