<?php
include "./dbinfo.php";
$url = parse_url(strtolower($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));
$url['path'] = str_replace("/index.php","",$url['path']);	// AllowOverride None 때문에 htaccess 사용이 불가능 할 수 있음
$paths = explode('/', $url['path']);

$event_domains = ['sstrong.co.kr']; //Hotevent 경유하지 않는 event 자체 도메인. (외부도메인이 경로(/event/{no})가 아닌 event와 동일)
if(!preg_match('/^(local\.)?event\.hotblood\.co\.kr/', $url['path']) && !in_array($_SERVER['HTTP_HOST'], $event_domains))
	$paths = array_slice($paths, 1);
//$paths = array_slice($paths, 1);
$uid = '';
if(preg_match('/^([A-Z]{2})?[0-9]+$/i', $paths[1])) {
	$uid = $paths[1];
	$method = @$paths[2];
} else {
	$method = $paths[1];
}

if(!$method) $method = 'view';

if(in_array($method, ['makehash'])) {
	$no = makeHash($paths[2]);
	echo "<a href='/{$no}'>{$no}</a>";
	exit;
}

if(in_array($method, ['kakao'])) {
	include "./lib/kakao/link.php";
	exit;
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

if(in_array($method, ['eventleads'])) {
	include "./lib/eventlead/EventLead.php";
	$eventLead = new EventLead();
	$eventLead->sendLeads();
	exit;
}

if(in_array($method, ['addmemo'])) {
	include "./zenith/updateMemo.php";
	$updateMemo = new UpdateMemo();
	exit;
}

if($method) { //저장된 메소드가 함수 일 경우 실행
	include "core.php";
	$event = new HotbloodEvent($paths);
	$event->{$method}();
}

function makeHash($uid) {
	$ab = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$s_id = str_split($uid);
	$make_hash = [0,0];
	for($i=0; $i<count($s_id); $i++) $make_hash[0] += $s_id[$i]*($i+$s_id[count($s_id)-1]);
	for($i=0; $i<count($s_id); $i++) $make_hash[1] += $s_id[$i]*($i+$s_id[0]);
	$make_hash = array_map(function($v) use($ab){$chksum = ($v % 26); return $ab[$chksum];}, $make_hash);
	$hash = implode("", $make_hash);
	$result = $hash.$uid;
	return $result;
}
?>