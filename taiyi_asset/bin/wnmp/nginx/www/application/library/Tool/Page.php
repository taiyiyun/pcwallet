<?php
class Tool_Page{
	/**
	 * 当前页码
	 */
	public $mPage = 1;

	/**
	 * 每页显示条数
	 */
	public $mPagesize;

	/**
	 * 最大页码
	 */
	public $mPageMax;
	public $mCnt = 0;

	/**
	 * 构造函数
	 * @param int $pCnt 总记录数
	 * @param int $pPagesize 每页显示条数
	 */
	function __construct($pCnt, $pPagesize = 10){
		$this->mCnt = $pCnt;
		if($this->mPageMax = ceil($pCnt / $this->mPagesize = $pPagesize)){
			if(isset($_GET['p']) && ($this->mPage = abs($_GET['p'])) && ($this->mPageMax < $this->mPage)){
				$this->mPage = $this->mPageMax;
			}
		}
		$this->mPage || $this->mPage = 1;
	}

	/**
	 * 分页
	 */
	function limit(){
		return (($this->mPage - 1) * $this->mPagesize).','.$this->mPagesize;
	}

	/**
	 * 处理连接
	 */
	private $_href = '';
	function _default_href($pHref=''){
		if($pHref) return $this->_href = $pHref;
		if(!$this->_href){
			$tGet = $_GET;
			if(isset($tGet['p'])) unset($tGet['p']);
			$tUrl = isset($_SERVER['REDIRECT_URL'])? $_SERVER['REDIRECT_URL']: '';
			$tHref = $tUrl.(empty($tGet)? '?p=': '?'.http_build_query($tGet).'&p=');
			$this->_href = ' <a href="'.urldecode($tHref).'%d">%s</a> ';
		}
		return $this->_href;
	}

	/**
	 * 显示分页
	 */
	function show($pHref=''){
		$this->_default_href($pHref);
		if($this->mPageMax == 1 || $this->mPageMax == 0) return '';
		$tPage = array();
		# 当前之前
		$tMax = $this->mPageMax - $this->mPage > 5? 5: 10 - $this->mPageMax + $this->mPage;
		for ($i = 0; $i < $tMax; $i++) {
			if(($tNum = $this->mPage - $i) < 1) break;
			$tPage[] = $tNum;
		}
		$tPage && sort($tPage);
		# 当前之后
		($tMax = 10 - ($tCnt = count($tPage))) < 5 && $tMax = 5;
		($tMax > ($this->mPageMax - $this->mPage)) && $tMax = $this->mPageMax - $this->mPage;
		for ($i = 0; $i < $tMax; $tPage[] = ++$i + $this->mPage);
		# 渲染分页
		$tHtml = '<div class="page">';
		$tHtml .= $this->_make_href(1, '&lt;&lt;');
		# 上一页
		$tHtml .= $this->_make_href($this->mPage-1, ' &lt;');
		# 页码
		foreach ($tPage as $v1) $tHtml .= $this->_make_href($v1, $v1);
		# 下一页
		$tHtml .= $this->_make_href($this->mPage+1, ' &gt;');
		$tHtml .= $this->_make_href($this->mPageMax, '&gt;&gt;');
		return $tHtml.'</div>';
	}

	/**
	 * 制造链接(逻辑写的不好，代码比较丑)
	 * @param $p
	 * @param $t
	 * @return string
	 */
	function _make_href($p, $t){
		if($p > $this->mPageMax) {
			$tReplace = array('%d'=>$this->mPage, '%s'=>$t);
		} elseif($this->mPage == $p){
			$tReplace = array('">' => '" class="current">', '%d'=>$p, '%s'=>$t);
		} else {
			$tReplace = array('%d'=>$p, '%s'=>$t);
		}
		return strtr($this->_href, $tReplace);
	}
}
