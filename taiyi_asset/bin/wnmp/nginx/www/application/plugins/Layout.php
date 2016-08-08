<?php
class LayoutPlugin extends Yaf_Plugin_Abstract{

	private $_layoutDir;
	private $_layoutFile;
	private $_layoutVars = array();

	public function __construct($layoutFile, $layoutDir = null){
		$this->_layoutFile = $layoutFile;
		$this->_layoutDir = ($layoutDir)? $layoutDir: PATH_APP . '/views/';
	}

	public function __set($name, $value){
		$this->_layoutVars[$name] = $value;
	}

	public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response){
	}

	public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response){
	}

	public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response){
		# 得到 body 代码
		$body = $response->getBody();
		# 清理 body
		$response->clearBody();
		# 使用 布局
		$layout = new Yaf_View_Simple($this->_layoutDir);
		$layout->content = $body;
		$layout->assign('layout', $this->_layoutVars);
		$response->setBody($layout->render($this->_layoutFile));
	}

	public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response){
	}

	public function preResponse(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response){
	}

	public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response){
	}

	public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response){
	}
}