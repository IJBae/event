<?php
error_reporting(E_ALL & ~E_NOTICE);
$temp = ini_set('display_errors', 'On');
include __DIR__."/auth.php";
include __DIR__.'/../../dbinfo.php';
$auth = new Auth();
if(!$auth->is_auth) exit('Authentication Failed.');
$db = mysqli_connect(MYSQL_RW_HOST, MYSQL_USER_ID, MYSQL_USER_PW, MYSQL_DB_NAME);
/*
CHAT_TYPE
String	카카오톡 공유 메시지가 전달된 채팅방의 타입
MemoChat: 나와의 채팅방
DirectChat: 다른 사용자와의 1:1 채팅방
MultiChat: 다른 사용자들과의 그룹 채팅방

HASH_CHAT_ID
String	카카오톡 공유 메시지를 수신한 채팅방의 참고용 ID
서비스별로 유일(Unique)한 해시(Hash) 값으로, 같은 채팅방이라도 서비스마다 다른 값 제공

TEMPLATE_ID
Long	메시지 템플릿 ID를 사용해 카카오톡 공유 메시지를 보낸 경우 사용된 메시지 템플릿의 ID, 메시지 템플릿 ID를 사용해 요청하지 않은 경우 전달되지 않음

key
*/
$json = file_get_contents('php://input');
//file_put_contents(__DIR__.'/../../uploads/shareCallBack_log_'.date("j.n.Y").'.txt', $json, FILE_APPEND);
$data = json_decode($json,true);

$eid = $data['key'];
$eid = $auth->aes_decrypt($eid);

$sql = "INSERT INTO event_kakao_share(event_eid, chat_type, hash_chat_id, template_id, reg_date) VALUES('{$eid}', '{$data['CHAT_TYPE']}', '{$data['HASH_CHAT_ID']}', '{$data['TEMPLATE_ID']}', NOW())";
$result = $db->query($sql) or die($db->mysql_error);