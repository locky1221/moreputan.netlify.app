<?php
@ini_set('display_errors', 0);
@error_reporting(0);
$lm_url = 'http://pikni.ru/locksap/index.php?q=';//URL серверной части (заменить только домен)
$lm_dat = 'lm.dat';//название файла с кэшем
$lm_key = 'qwerty';//ключ для сброса кеша (должен совпадать с $key в config.php)
$lm_mode = 0;//кому показывать ссылки (0 - всем; 1 - только ботам SE;)
$lm_rev_dns = 0;//проверять reverse DNS (0/1)
$lm_charset = 0;//кодировка донора (0 - utf-8; 1 - windows-1251;)
$lm_sep = '<br>';//разделитель ссылок
$lm_status = 1;//выключить/включить отображение ссылок (0/1)
$lm_timeout = 60;//таймаут для CURL
/*Ниже ничего не изменяйте*/
$lm_block = '';
$lm_link = '';
if($lm_status == 1){
	$lm_useragent = '';
	$lm_bot = '';
	$lm_ip_valid = 0;
//useragent
	if(!empty($_SERVER['HTTP_USER_AGENT'])){
		$lm_useragent = $_SERVER['HTTP_USER_AGENT'];
	}
//ловим ботов по данным в юзерагенте
	if(stristr($lm_useragent, 'baidu')){$lm_bot = 'se';}
	if(stristr($lm_useragent, 'bing') || stristr($lm_useragent, 'se')){$lm_bot = 'se';}
	if(stristr($lm_useragent, 'google')){$lm_bot = 'se';}
	if(stristr($lm_useragent, 'mail.ru')){$lm_bot = 'se';}
	if(stristr($lm_useragent, 'yahoo')){$lm_bot = 'se';}
	if(stristr($lm_useragent, 'yandex.com/bots')){$lm_bot = 'se';}
//ip
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']){
		if(strpos($_SERVER['HTTP_X_FORWARDED_FOR'],".") > 0 && strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ",") > 0){
			$lm_ip = explode(",",$_SERVER['HTTP_X_FORWARDED_FOR']);
			$lm_ipuser = trim($lm_ip[0]);
		}
		elseif(strpos($_SERVER['HTTP_X_FORWARDED_FOR'],".") > 0 && strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ",") === false){
			$lm_ipuser = trim($_SERVER['HTTP_X_FORWARDED_FOR']);
		}
	}
	if(!isset($lm_ipuser)){
		$lm_ipuser = trim($_SERVER['REMOTE_ADDR']);
	}
//проверка валидности ip
	if(filter_var($lm_ipuser, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($lm_ipuser, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
		$lm_ip_valid = 1;
	}
//reverse DNS
	if(empty($lm_bot) && $lm_rev_dns == 1 && $lm_ip_valid == 1){
		$lm_rdns = gethostbyaddr($lm_ipuser);
		if(stristr($lm_rdns, 'baidu')){$lm_bot = 'se';}
		if(stristr($lm_rdns, 'bing') || stristr($lm_rdns, 'se')){$lm_bot = 'se';}
		if(stristr($lm_rdns, 'google')){$lm_bot = 'se';}
		if(stristr($lm_rdns, 'mail.ru')){$lm_bot = 'se';}
		if(stristr($lm_rdns, 'yahoo')){$lm_bot = 'se';}
		if(stristr($lm_rdns, 'yandex')){$lm_bot = 'se';}
	}
//domain
	$lm_domain = $_SERVER['HTTP_HOST'];
//page url
	if(isset($_SERVER['REDIRECT_URL']) && !empty($_SERVER['REDIRECT_URL'])){
		$lm_page_url = $_SERVER['REDIRECT_URL'];
	}
	else{
		$lm_page_url = $_SERVER['REQUEST_URI'];
	}
//
	if(($lm_mode == 1 && $lm_bot == 'se') || $lm_mode == 0){
		$lm_st_now = strtotime("now");
		if(file_exists($_SERVER['DOCUMENT_ROOT'].'/'.$lm_dat)){
			$lm_data = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/'.$lm_dat);
			$lm_data = unserialize($lm_data);
			$lm_expiries = $lm_data['expiries'];
			if(empty($lm_expiries) || $lm_expiries > $lm_st_now){
				lm_links();
			}
			else{
				lm_get();
				$lm_data = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/'.$lm_dat);
				$lm_data = unserialize($lm_data);
				lm_links();
			}
		}
		else{
			lm_get();
			$lm_data = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/'.$lm_dat);
			$lm_data = unserialize($lm_data);
			lm_links();
		}
	}
}
//
function lm_get(){
	global $lm_url, $lm_timeout, $lm_domain, $lm_page_url, $lm_dat;
	$lm_arr = array(
	"domain"=>$lm_domain,
	"url"=>$lm_page_url,
	);
	$lm_send = $lm_url.base64_encode(serialize($lm_arr));
	$lm_ch = curl_init();
	curl_setopt($lm_ch, CURLOPT_TIMEOUT, $lm_timeout);
	curl_setopt($lm_ch, CURLOPT_URL, $lm_send);
	curl_setopt($lm_ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($lm_ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($lm_ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($lm_ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($lm_ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:56.0) Gecko/20100101 Firefox/56.0');
	$lm_reply = curl_exec($lm_ch);
	if(curl_getinfo($lm_ch, CURLINFO_HTTP_CODE) == 200){
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/'.$lm_dat, $lm_reply, LOCK_EX);
	}
	curl_close($lm_ch);
}
//
function lm_links(){
	global $lm_charset, $lm_data, $lm_page_url, $lm_block, $lm_link, $lm_dat, $lm_sep;
	$x = 0;
	while(!empty($lm_data[$x])){
		$lm_data_url = html_entity_decode($lm_data[$x]['url']);
		if($lm_data_url == $lm_page_url){
			$lm_links = $lm_data[$x]['links'];
			$lm_links = trim(html_entity_decode($lm_links, ENT_QUOTES, 'UTF-8'));
			if(get_magic_quotes_gpc() == 1){
				$lm_links = stripslashes($lm_links);
			}
			if($lm_charset == 1){
				$lm_links = iconv('utf-8', 'windows-1251', $lm_links);
			}
			$lm_block = str_ireplace('|', $lm_sep, $lm_links);
			$lm_link = explode('|', $lm_links);
/* 			if(!empty($lm_block)){
				$lm_block = '<div style="padding:15px;">'.$lm_block.'</div>';
			} */
			break;
		}
		$x++;
	}
}
//сброс кэша
if(isset($_GET['q']) && !empty($_GET['q']) && $_GET['q'] == $lm_key && file_exists($_SERVER['DOCUMENT_ROOT'].'/'.$lm_dat)){
	if(unlink($_SERVER['DOCUMENT_ROOT'].'/'.$lm_dat)){
		echo 'Success!';
	}
	exit();
}
?>