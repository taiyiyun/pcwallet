<?php
/**
 * 开放接口
 * Class Api_Btctrade
 */
class Api_Btctrade{

	/**
	 * API地址
	 *
	 * @var string
	 */
	private $apiurl = 'http://api.btctrade.com/api/';

	/**
	 * 公钥
	 * 申请地址：https://www.btctrade.com/user_exchange/api/
	 *
	 * @var string
	 */
	private $key = 'xxxxx-xxxxx-xxxxx-xxxxx-xxxxx-xxxxx-xxxxx';

	/**
	 * 私钥(口令)
	 * 申请地址：https://www.btctrade.com/user_exchange/api/
	 *
	 * @var string
	 */
	private $passphrase = 'xxxxxxxx';

	/**
	 * API密钥存放目录
	 *
	 * 注意：安全、可写
	 * 安全：将文件放到外网不能访问的目录
	 * 可写：文件可以不存在，但目录必须存在，并且目录有写权限
	 *
	 * @var string
	 */
	public $secret_file = './secret.store.dat';

	/**
	 * 临时密钥
	 *
	 * @var string
	 */
	private $secret = false;
	private $secret_expiry = false;

	/**
	 * 调试模式
	 *
	 * @var bool
	 */
	private $debug = false;

	/**
	 * 构造函数
	 */
	public function __construct($key = false, $passphrase = false, $secret_file = false){
		$key && $this->key = $key;
		$passphrase && $this->passphrase = $passphrase;
		$secret_file && $this->secret_file = $secret_file;
		$this->load_secret();
	}

	/**
	 * 获取市场信息
	 *
	 * @return array
	 * @access open
	 */
	public function ticker(){
		return $this->request('ticker', array(), 'GET', false);
	}

	/**
	 * 查看市场深度
	 *
	 * @return array
	 * @access open
	 */
	public function get_depth(){
		return $this->request('depth', array(), 'GET', false);
	}

	/**
	 * 查看市场行情
	 *
	 * @param $since -1~9999999999 查询范围
	 *
	 * @return array
	 * @access open
	 */
	public function get_trades($since = -1){
		return $this->request('trades', array('since' => $since), 'GET', false);
	}

	/**
	 * Account Balance(账户信息)
	 *
	 * @return array
	 * @access readonly
	 */
	public function get_balance(){
		return $this->request('balance', array(), 'POST');
	}

	/**
	 * 获取转入比特币地址
	 *
	 * @return array
	 * @access readonly
	 */
	public function get_wallet(){
		return $this->request('wallet', array(), 'POST');
	}

	/**
	 * 获得你最近的挂单
	 *
	 * @param integer $since 指定时间后的挂单（默认返回所有）
	 * @param string open|all $type 类型
	 *
	 * @return array
	 * @access readonly
	 */
	public function get_orders($since = 0, $type = 'open'){
		return $this->request('orders', array('since' => $since, 'type' => $type), 'POST');
	}


	/**
	 * 获取挂单的详细信息
	 *
	 * @param integer $orderid 挂单ID
	 *
	 * @return array
	 * @access readonly
	 */
	public function fetch_order($orderid){
		return $this->request('fetch_order', array('id' => $orderid), 'POST');
	}

	/**
	 * 取消挂单
	 *
	 * @param integer $orderid 取消挂单ID
	 *
	 * @return array
	 * @access full
	 */
	public function cancel_order($orderid){
		return $this->request('cancel_order', array('id' => $orderid), 'POST');
	}

	/**
	 * 购买比特币
	 *
	 * @param float $amount 购买数量
	 * @param float $price 购买价格
	 *
	 * @return array
	 * @access full
	 */
	public function add_buy($amount, $price){
		return $this->request('buy', array('amount' => $amount, 'price' => $price), 'POST');
	}

	/**
	 * 出售比特币
	 *
	 * @param float $amount 购买数量
	 * @param float $price 购买价格
	 *
	 * @return array
	 * @access full
	 */
	public function add_sell($amount, $price){
		return $this->request('sell', array('amount' => $amount, 'price' => $price), 'POST');
	}

	/**
	 * 执行请求
	 *
	 * @param string $method API地址
	 * @param array $params 请求参数
	 * @param string GET|POST $http_method 请求方式
	 * @param bool $auth 是否需要认证
	 *
	 * @return array
	 */
	protected function request($method, $params = array(), $http_method = 'GET', $auth = true){
		if($auth){
			if(60 > $this->secret_expiry - time()){
				$this->refresh_secret();
			}
			# 唯一的数字
			$mt = explode(' ', microtime());
			$params['nonce'] = $mt[1] . substr($mt[0], 2, 2);
			# 认证信息
			$params['key'] = $this->key;
			$params['signature'] = hash_hmac('sha256', http_build_query($params, '', '&'), $this->secret);
		}
		# 数据字符串
		$data = http_build_query($params, '', '&');
		$data = $this->do_curl($method, $data, ($http_method == 'GET'? 'GET': 'POST'));
		return $data;
	}

	/**
	 * 加载密钥
	 *
	 * @return bool
	 */
	private function load_secret(){
		# 存在密钥文件
		if(file_exists($this->secret_file)){
			$storTime = @filemtime($this->secret_file);
			# 密钥有效
			if(7200 > time() - $storTime){
				$this->secret = trim(file_get_contents($this->secret_file));
				$this->secret_expiry = $storTime + 7200;
				return true;
			}
		}
		return $this->refresh_secret();
	}

	/**
	 * 获取新密钥
	 *
	 * @return bool
	 */
	private function refresh_secret(){
		$param = 'api_passphrase=' . urlencode($this->passphrase).'&key='.$this->key;
		$data = $this->do_curl('getsecret', $param, 'POST');
		if($data['result'] && $this->secret = $data['data']['secret']){
			file_put_contents($this->secret_file, $this->secret);
			$this->secret_expiry = time() + 7200;
			return true;
		}
		return false;
	}

	/**
	 * 执行请求
	 *
	 * @param string $path API方法
	 * @param string $data 提交数据
	 * @param string GET|POST $http_method 请求方式
	 *
	 * @return array
	 */
	private function do_curl($path, $data, $http_method){
		static $ch = null;
		$url = $this->apiurl . $path;
		if($this->debug){
			echo "Sending request to $url\n";
		}
		if(is_null($ch)){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; BtcTrade PHP client; ' . php_uname('s') . '; PHP/' . phpversion() . ')');
		}
		if($http_method == 'GET'){
			$url .= '?' . $data;
		}
		else{
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		$response = curl_exec($ch);
		if($this->debug){
			echo "Response: $response\n";
		}
		if(empty($response)){
			throw new Exception('Could not get reply: ' . curl_error($ch));
		}
		$data = json_decode($response, true);
		if(!is_array($data)){
			throw new Exception('Invalid data received');
		}
		return $data;
	}
}