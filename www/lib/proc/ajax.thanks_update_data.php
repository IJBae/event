<?php 
//thanks 페이지에서 받은 데이터를 업데이트 

include '../../dbinfo.php';

$db = mysqli_connect(MYSQL_RW_HOST, MYSQL_USER_ID, MYSQL_USER_PW, MYSQL_DB_NAME);
$db->query("SET SESSION character_set_client=utf8mb4;");
$db->query("SET SESSION character_set_connection=utf8mb4;");

$eid = $_POST['eid'];
$date = $_POST['date'];

if(empty($seq) || empty($eid)){echo false;}

$sql = "UPDATE app_subscribe SET add8 = '$date' WHERE eid = {$eid}";
$chainsawResult = $db->query($sql);

if($chainsawResult) { //저장 완료 시
    echo true;
} else {
    echo false;
}

exit;
?>
