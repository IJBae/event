<?php
/** AJAX 로 받은 댓글을 DB로 저장 *******
 * TABLE : EVENT_REPLY
 * 
 * /home/event/www/lib/proc/ajax.event_reply.php
 *****************************************/
include '/home/event/www/dbinfo.php';

$db = mysqli_connect(MYSQL_RW_HOST, MYSQL_USER_ID, MYSQL_USER_PW, MYSQL_DB_NAME);
$db->query("SET SESSION character_set_client=utf8mb;");
$db->query("SET SESSION character_set_connection=utf8mb;");

// echo "<pre>".print_r($_POST)."</pre>";

$sql = "INSERT INTO event_reply(event_seq, reply, er_datetime) VALUES({$_POST['event_seq']}, '{$_POST['reply']}', NOW())";
$result = $db->query($sql) or die($db->mysql_error);
if($result) { //저장 완료 시
    echo "SUCCESS";
} else {
    echo "ERROR";
}

$db->close();
exit;
?>