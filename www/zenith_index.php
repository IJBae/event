<?php
if (!defined('__DEV__')) {
    $dev = false;
    if (preg_match('/^local\./', $_SERVER['HTTP_HOST'])) { // Local server
        $dev = true;
    }
    define('__DEV__', $dev);
}

$url = parse_url(strtolower($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));
$url['path'] = str_replace("/zenith_index.php","",$url['path']);	// AllowOverride None 때문에 htaccess 사용이 불가능 할 수 있음
$paths = explode('/', $url['path']);

if(!preg_match('/^(local\.)?event\.hotblood\.co\.kr/', $url['path']))
	$paths = array_slice($paths, 1);
//$paths = array_slice($paths, 1);
$uid = '';

$method = '';
if(isset($paths[1])){
	if(preg_match('/[0-9]+/', $paths[1])) {
		$uid = $paths[1];
		$method = $paths[2] ?? '';
	} else {
		$method = $paths[1];
	}
}

if(!$method){
	$method = 'view';
}

if(in_array($method, ['oauth'])) {
	$provider = @$paths[2];
	include "./lib/hybridauth3/auth.php";
	exit;
}

if(in_array($method, ['kakaocallback'])) {
	$provider = @$paths[2];
	include "./lib/kakaoshare/callback.php";
	exit;
}

if($method) { //저장된 메소드가 함수 일 경우 실행
	include "./zenith/zenith_core.php";
	$event = new HotbloodEventZenith($paths);
	$event->{$method}();
}
?>