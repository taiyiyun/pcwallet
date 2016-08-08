<?php
class Tool_Md5{
    /**
     * 邮箱激活码生成
     *
     * @param $pEmail 邮箱
     * @param int $pRegtime 注册时间  
     * @return string
     */ 
    static function emailActivateKey($pEmail , $pRegtime){
        return md5(md5($pEmail) . $pRegtime . md5('ybex'));        
    } 
}
