<?php
class Open_Btcc
{
    /*
     * 交易api地址
     */
    static $trade_api = 'https://api.btcchina.com/api_trade_v1.php';
    /*
     * payment地址
     */
    static $payment_api = 'https://api.btcchina.com/api.php/payment';

    public static function sign($method, $params = array()){
        $accessKey = "89178abb-0309-4b9a-a4a8-eefff73ce93b"; 
        $secretKey = "b9401a66-800f-480b-83fb-75616b770f41"; 

        $mt = explode(' ', microtime());
        $ts = $mt[1] . substr($mt[0], 2, 6);

        $signature = urldecode(http_build_query(array(
            'tonce' => $ts,
            'accesskey' => $accessKey,
            'requestmethod' => 'post',
            'id' => 1,
            'method' => $method,
            'params' => implode(',', $params),
        )));

        $hash = hash_hmac('sha1', $signature, $secretKey);

        return array(
            'ts' => $ts,
            'hash' => $hash,
            'auth' => base64_encode($accessKey.':'. $hash)
        );
    }
     
    /**
     * 请求入口
     * @param method btcc接口名称
     * @param api 接口类型,trade|payment
     *
     */
    public static function request($method, $params=array(), $api='trade'){
        $sign = self::sign($method, $params);

        $options = array( 
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . $sign['auth'],
                'Json-Rpc-Tonce: ' . $sign['ts'],
            )
        );

        $postData = json_encode(array(
            'method' => $method,
            'params' => $params,
            'id' => 1,
        ));

        $headers = array(
            'Authorization: Basic ' . $sign['auth'],
            'Json-Rpc-Tonce: ' . $sign['ts'],
        );        
        if(empty($api) || $api == 'trade'){
            $api_url = self::$trade_api; 
        } elseif($api == 'payment'){
            $api_url = self::$payment_api; 
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; BTC China Trade Bot; '.php_uname('a').'; PHP/'.phpversion().')');

        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $res = curl_exec($ch);
        return json_decode($res, true);
    }
    /**
     * 获取消息
     */
    public static function getInfo(){
        return self::request('getAccountInfo');
    }
    /**
     * 创建充值
     * @param data array(price,currency,notificationURL)
     */
    public static function createPurchaseOrder($data){
        if(!isset($data['price'],$data['currency'],$data['notificationURL'])){
            return false;
        }
        return self::request('createPurchaseOrder', $data, 'payment');
    }
    /**
     * 充值查询
     * @param id txid
     */
    public static function getPurchaseOrder($id){
        if(!$id = intval($id)){
            return false;
        }
        $data = array($id);
        return self::request('getPurchaseOrder', $data, 'payment');
    }
}
