<?php
class Tool_Str{
	static function safestr($pStr, $pDefault=false){
		if(!$pStr = htmlspecialchars($pStr)){
			return $pDefault;
		}
		return $pStr;
	}

	/**
	 * 替换危险字符串
	 *
	 * @param str $pStr 危险字符
	 * @param array $pTrans 自定义替换规则
	 * @return str 安全字符
	 */
	static function filter($pStr, $pTrans=array()){
		$tTrans = array("'"=>'', '"'=>'', '`'=>'', '\\'=>'', '<'=>'＜', '>'=>'＞');
		return strtr(trim($pStr), array_merge($tTrans, $pTrans));
	}

	/**
	 * 获得 KEY 对应的 数组值
	 *
	 * @param array $pArr
	 * @param str $pKey
	 * @param str $pDefault
	 */
	static function arr2str($pArr, $pKey, $pDefault=''){
		return isset($pArr[$pKey])? $pArr[$pKey]: $pDefault;
	}

	/**
	 * ID 转为 图片文件路径
	 * @param int $pId
	 * @return str
	 */
	static function id2path($pId){
		$tPid = str_pad($pId, 9, 0, 0);
		return array(substr($tPid, 0, 3).'/'.substr($tPid, 3, 3).'/', substr($tPid, 6));
	}

	/**
	 * 格式化数字
	 * @param $pNum
	 * @param int $pLen
	 * @param int $pRule 规则 0:四舍五入, 1:全入, 2:全舍
	 * @return int | float
	 */
	static function format($pNum, $pLen = 2, $pRule = 0){
		# 整数部分
		$tInt = intval($pNum);
		# 无小数直接返回
		if(!$tPos = strpos($pNum, '.')) return $tInt;#return $pNum;
		# 小数部分
		$tNum = substr($pNum, $tPos+1);
		# 指定长度
		$tReturn = (float)('0.'.substr($tNum, 0, $pLen));
		# 四舍五入
		if(((0 == $pRule) && (isset($tNum{$pLen}) && ($tNum{$pLen} > 5))) || ((1 == $pRule) && intval(substr($tNum, $pLen)))){
			$tReturn = (float)bcadd($tReturn, (float)('0.'.str_pad('', $pLen-1, 0).'1'), $pLen);
		}
		return (float)bcadd($tInt, $tReturn, $pLen);
	}
}
