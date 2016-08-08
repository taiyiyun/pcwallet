<?php
echo "init for taiyi.db\n";

//从外网站点www/clientapi.taiyilabs.com/taiyi.db获取taiyi.db 。
//这个方法耗时不定，为保证用户操作顺畅，nginx设置超时，这一方法不宜放在nginx启动之后；

//下载db
$url_db = 'http://clientapi.taiyilabs.com/taiyi.db';
$db_name = '../user/webdb/init/temp/taiyi.db';
mkdir('../user/webdb/init/temp/');
Tool_Fnc::getFile($url_db, $db_name);
//复制db
$db_file = '../user/webdb/taiyi.db';
if(filesize($db_name)>0){
	copy($db_name, $db_file);
}

//下载图片
$url_img = 'http://clientapi.taiyilabs.com/images.zip';
$img_name = '../user/webdb/init/temp/images.zip';
$img_dir = '../user/webdb/init/temp/images';
mkdir('../user/webdb/init/temp/');
Tool_Fnc::getFile($url_img, $img_name);
//解压图片
$zip=new ZipArchive;
if($zip->open($img_name)===TRUE){
	$zip->extractTo($img_dir);
	$zip->close();
}
//更新图片
$dst_dir = '../bin/wnmp/nginx/www/public/images';
Tool_Fnc::copydir($img_dir, $dst_dir);







class Tool_Fnc{
	/*
	请求文件
	@para string $url 图片的url地址
	@para string $filename 下载到本地的文件名地址
	*/
	static function getFile($url, $fileName)  
    {  
        $ch = curl_init();  
        $fp = fopen($fileName, 'wb');  
        curl_setopt($ch, CURLOPT_URL, $url);  
        curl_setopt($ch, CURLOPT_FILE, $fp);  
        curl_setopt($ch, CURLOPT_HEADER, 0);  
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);  
        curl_exec($ch);  
        curl_close($ch);  
        fclose($fp);  
    }
	/*
	复制文件
	@para string $src 
	@para string $des
	*/
	static function copydir($src,$des) {
		$dir = opendir($src);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				copy($src . '/' . $file,$des . '/' . $file);
			}
		}
		closedir($dir);
	}
}
?> 