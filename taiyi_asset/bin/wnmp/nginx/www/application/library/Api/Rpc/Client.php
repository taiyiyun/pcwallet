<?php
class Api_Rpc_Client{
    private $debug;
    private $url;
    private $id = 1;
    private $notification = false;

    public function __construct($url, $debug = false){
        $this->url = $url;
        $this->debug = $debug;
    }

    public function setRPCNotification($notification){
        $this->notification = !empty($notification);
    }
    
    public function __call($method, $params){
        if(!is_scalar($method)){
            throw new Exception('Method name has no scalar value');
        }

        if(is_array($params)){
            $params = array_values($params);
        }
        else{
            throw new Exception('Params must be given as array');
        }
        if($this->notification){
            $currentId = null;
        }
        else{
            $currentId = $this->id;
        }
        $request = array('method' => $method, 'params' => $params, 'id' => $currentId);
        $request = json_encode($request);
        $this->debug && $this->debug .= '***** Request *****' . "\n" . $request . "\n" . '***** End Of request *****' . "\n\n";
        $opts = array('http' => array('method' => 'POST', 'header' => 'Content-type: application/json', 'content' => $request));
        $context = stream_context_create($opts);
        //die($this->url);
        if($fp = fopen($this->url, 'r', false, $context)){
            $response = '';
            while($row = fgets($fp)){
                $response .= trim($row) . "\n";
            }
            $this->debug && $this->debug .= '***** Server response *****' . "\n" . $response . '***** End of server response *****' . "\n";
            $response = json_decode($response, true);
        }
        else{
            throw new Exception('Unable to connect to 钱包 ');
        }
        if($this->debug){
            //echo nl2br($this->debug);
        }
        if(!$this->notification){
            if($response['id'] != $currentId){
                throw new Exception('Incorrect response id (request id: ' . $currentId . ', response id: ' . $response['id'] . ')');
            }
            if(!is_null($response['error'])){
                throw new Exception('Request error: ' . $response['error']);
            }
            return $response['result'];
        }
        return true;
    }
    
    static function getBalance($coin){
        if(empty($coin)){
            return FALSE;
        }
        $tARC = new Api_Rpc_Client(Yaf_Application::app()->getConfig()->rpcapi->$coin);
        $info    = $tARC->getinfo();
        $balance = $info['balance'];
        return ($balance ? $balance : 0);
    }
    /**
     * 得到钱包地址
     *
     * @param $pKey
     * @param bool $pCache 0:生成新的 1:使用缓存 2:有缓存返回,无缓存生成
     * @return mixed
     */
    static function getWalletByCache($pKey, $pCache, $coin){
        $tRedis = & Cache_Redis::instance();
        if($pCache && $tAddr = $tRedis->hget($coin . 'addr', $pKey)){
            return $tAddr;
        }
        if(1 == $pCache){
            return false;
        }
        #die(Yaf_Application::app()->getConfig()->rpcapi->$coin);
        if(!$tConfig = Yaf_Application::app()->getConfig()->rpcapi->$coin){
            return false;
        }
        $tARC = new Api_Rpc_Client($tConfig);
        $tAddr = $tARC->getnewaddress($pKey);
        $tRedis->hset($coin . 'addr', $pKey, $tAddr);
        return $tAddr;
    }

    /**
     * 发钱给用户
     * @param $address 钱包地址
     * @param $amount 钱数
     * @param string $coin 币种
     * @return bool
     */
    static function sendToUserAddress($address, $amount, $coin){
        //echo $address;
        # 配置
        //die($coin);
        //echo Yaf_Application::app()->getConfig()->rpcapi->$coin;
        if(!$tConfig = Yaf_Application::app()->getConfig()->rpcapi->$coin){
            return false;
        }
        $tARC = new Api_Rpc_Client($tConfig,true);
        $fee = ($coin == 'btc'?-0.0002:-0.001);
        $amount = bcadd($amount, $fee, 8);
        $amount = floatval($amount);
    //    $tARC->settxfee(0.0001);
       // echo $address.'--'.$amount;exit(0);
        $tTxid = $tARC->sendtoaddress($address, $amount);
        if(strlen($tTxid) != 64){
            return false;
        }
        return $tTxid;
    }
}
